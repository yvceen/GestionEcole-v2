<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeedbackCase;
use App\Models\FeedbackCaseMessage;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MobileFeedbackCaseController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $parent = $this->parent($request);
        $children = $this->children($parent);
        $items = FeedbackCase::query()->where('school_id', $parent->school_id)->where('submitted_by_user_id', $parent->id)
            ->with(['student.classroom:id,name'])->withCount('messages')->latest()->limit(100)->get();

        return response()->json([
            'items' => $items->map(fn (FeedbackCase $case) => $this->payload($case))->values(),
            'children' => $children->map(fn (Student $child) => ['id' => $child->id, 'name' => $child->full_name, 'classroom' => $child->classroom?->name ?? ''])->values(),
            'kinds' => FeedbackCase::kinds(),
            'categories' => FeedbackCase::categories(),
        ]);
    }

    public function show(Request $request, FeedbackCase $feedbackCase): JsonResponse
    {
        $parent = $this->parent($request);
        $this->authorizeCase($parent, $feedbackCase);
        $feedbackCase->load(['student.classroom:id,name', 'messages' => fn ($query) => $query->where('is_internal', false)->with('user:id,name,role')->orderBy('created_at')]);

        return response()->json(['item' => $this->payload($feedbackCase), 'messages' => $feedbackCase->messages->map(fn (FeedbackCaseMessage $message) => [
            'id' => $message->id,
            'message' => $message->message,
            'author' => $message->user?->name ?? '',
            'role' => $message->user?->role ?? '',
            'created_at' => $message->created_at?->toIso8601String(),
        ])->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $parent = $this->parent($request);
        $childIds = $this->children($parent)->pluck('id')->all();
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(FeedbackCase::kinds()))],
            'category' => ['required', Rule::in(array_keys(FeedbackCase::categories()))],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'student_id' => ['nullable', 'integer', Rule::in($childIds)],
            'is_confidential' => ['nullable', 'boolean'],
        ]);
        $case = FeedbackCase::create([
            ...$data,
            'school_id' => $parent->school_id,
            'submitted_by_user_id' => $parent->id,
            'reference' => 'REC-' . now()->format('ymd') . '-' . strtoupper(Str::random(5)),
            'status' => 'new',
            'priority' => 'normal',
            'is_confidential' => (bool) ($data['is_confidential'] ?? false),
        ]);
        $this->notifyManagers($case, 'Nouvelle ' . mb_strtolower(FeedbackCase::kinds()[$case->kind]), $case->reference . ' · ' . $case->subject);

        return response()->json(['message' => 'Demande envoyée.', 'item' => $this->payload($case->load('student.classroom:id,name'))], 201);
    }

    public function reply(Request $request, FeedbackCase $feedbackCase): JsonResponse
    {
        $parent = $this->parent($request);
        $this->authorizeCase($parent, $feedbackCase);
        abort_if($feedbackCase->status === 'closed', 422);
        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        FeedbackCaseMessage::create(['feedback_case_id' => $feedbackCase->id, 'user_id' => $parent->id, 'message' => $data['message'], 'is_internal' => false]);
        $this->notifyManagers($feedbackCase, 'Nouvelle réponse du parent', $feedbackCase->reference . ' · ' . $feedbackCase->subject);

        return response()->json(['message' => 'Réponse envoyée.']);
    }

    private function parent(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && (string) $user->role === User::ROLE_PARENT, 403);
        return $user;
    }

    private function authorizeCase(User $parent, FeedbackCase $case): void
    {
        abort_unless((int) $case->school_id === (int) $parent->school_id && (int) $case->submitted_by_user_id === (int) $parent->id, 404);
    }

    private function children(User $parent)
    {
        return Student::query()->active()->where('school_id', $parent->school_id)->where('parent_user_id', $parent->id)
            ->with('classroom:id,name')->orderBy('full_name')->get(['id', 'full_name', 'classroom_id']);
    }

    private function notifyManagers(FeedbackCase $case, string $title, string $body): void
    {
        $ids = User::query()->where('school_id', $case->school_id)->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE])->pluck('id')->all();
        $this->notifications->notifyUsers($ids, 'feedback_case', $title, $body, ['feedback_case_id' => $case->id, 'school_id' => $case->school_id]);
    }

    private function payload(FeedbackCase $case): array
    {
        return [
            'id' => $case->id,
            'reference' => $case->reference,
            'kind' => $case->kind,
            'kind_label' => FeedbackCase::kinds()[$case->kind] ?? $case->kind,
            'category' => $case->category,
            'category_label' => FeedbackCase::categories()[$case->category] ?? $case->category,
            'subject' => $case->subject,
            'description' => $case->description,
            'status' => $case->status,
            'status_label' => FeedbackCase::statuses()[$case->status] ?? $case->status,
            'priority' => $case->priority,
            'is_confidential' => (bool) $case->is_confidential,
            'messages_count' => (int) ($case->messages_count ?? $case->messages()->count()),
            'student' => ['id' => $case->student_id, 'name' => $case->student?->full_name ?? '', 'classroom' => $case->student?->classroom?->name ?? ''],
            'created_at' => $case->created_at?->toIso8601String(),
        ];
    }
}
