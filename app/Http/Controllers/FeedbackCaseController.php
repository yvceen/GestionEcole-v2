<?php

namespace App\Http\Controllers;

use App\Models\FeedbackCase;
use App\Models\FeedbackCaseMessage;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FeedbackCaseController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($user);
        $canManage = $this->canManage($user);
        $status = trim((string) $request->get('status', ''));
        $kind = trim((string) $request->get('kind', ''));
        $q = trim((string) $request->get('q', ''));
        $cases = FeedbackCase::query()->where('school_id', $schoolId)
            ->when(!$canManage && (string) $user->role !== User::ROLE_DIRECTOR, fn ($query) => $query->where('submitted_by_user_id', $user->id))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($kind !== '', fn ($query) => $query->where('kind', $kind))
            ->when($q !== '', fn ($query) => $query->where(function ($nested) use ($q) {
                $nested->where('reference', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%")
                    ->orWhereHas('submitter', fn ($submitter) => $submitter->where('name', 'like', "%{$q}%"));
            }))
            ->with(['submitter:id,name,role', 'student.classroom:id,name', 'assignedTo:id,name'])
            ->withCount('messages')->latest()->paginate(20)->withQueryString();

        $base = FeedbackCase::query()->where('school_id', $schoolId)
            ->when(!$canManage && (string) $user->role !== User::ROLE_DIRECTOR, fn ($query) => $query->where('submitted_by_user_id', $user->id));
        $stats = [
            'new' => (clone $base)->where('status', 'new')->count(),
            'reviewing' => (clone $base)->whereIn('status', ['reviewing', 'waiting_submitter'])->count(),
            'resolved' => (clone $base)->where('status', 'resolved')->count(),
            'complaints' => (clone $base)->where('kind', 'complaint')->count(),
        ];

        return view('feedback-cases.index', $this->viewData($user, compact('cases', 'stats', 'status', 'kind', 'q', 'canManage')));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $children = (string) $user->role === User::ROLE_PARENT
            ? Student::query()->active()->where('school_id', $this->schoolId($user))->where('parent_user_id', $user->id)->with('classroom:id,name')->orderBy('full_name')->get()
            : collect();

        return view('feedback-cases.create', $this->viewData($user, compact('children')));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($user);
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(FeedbackCase::kinds()))],
            'category' => ['required', Rule::in(array_keys(FeedbackCase::categories()))],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'student_id' => ['nullable', 'integer'],
            'is_confidential' => ['nullable', 'boolean'],
        ]);
        if (!empty($data['student_id'])) {
            $student = Student::query()->where('school_id', $schoolId)->findOrFail($data['student_id']);
            if ((string) $user->role === User::ROLE_PARENT) {
                abort_unless((int) $student->parent_user_id === (int) $user->id, 403);
            }
        }
        $case = FeedbackCase::create([
            ...$data,
            'school_id' => $schoolId,
            'submitted_by_user_id' => $user->id,
            'reference' => 'REC-' . now()->format('ymd') . '-' . strtoupper(Str::random(5)),
            'priority' => 'normal',
            'status' => 'new',
            'is_confidential' => $request->boolean('is_confidential'),
        ]);
        $this->notifyManagers($case, 'Nouvelle ' . mb_strtolower(FeedbackCase::kinds()[$case->kind]), $case->reference . ' · ' . $case->subject);

        return redirect()->route($this->routePrefix($user) . '.show', $case)->with('success', 'Votre demande a été enregistrée sous la référence ' . $case->reference . '.');
    }

    public function show(Request $request, FeedbackCase $feedbackCase)
    {
        $this->authorizeCase($request->user(), $feedbackCase);
        $canManage = $this->canManage($request->user());
        $feedbackCase->load(['submitter:id,name,email,phone,role', 'student.classroom:id,name', 'assignedTo:id,name', 'messages' => fn ($query) => $query->with('user:id,name,role')->orderBy('created_at')]);
        $managers = $canManage ? User::query()->where('school_id', $feedbackCase->school_id)->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE])->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']) : collect();

        return view('feedback-cases.show', $this->viewData($request->user(), compact('feedbackCase', 'canManage', 'managers')));
    }

    public function reply(Request $request, FeedbackCase $feedbackCase)
    {
        $user = $request->user();
        $this->authorizeCase($user, $feedbackCase);
        abort_if(in_array($feedbackCase->status, ['closed'], true), 422, 'Cette demande est clôturée.');
        $data = $request->validate(['message' => ['required', 'string', 'max:5000'], 'is_internal' => ['nullable', 'boolean']]);
        $internal = $this->canManage($user) && $request->boolean('is_internal');
        FeedbackCaseMessage::create(['feedback_case_id' => $feedbackCase->id, 'user_id' => $user->id, 'message' => $data['message'], 'is_internal' => $internal]);

        if ($this->canManage($user)) {
            $feedbackCase->update(['first_response_at' => $feedbackCase->first_response_at ?: now(), 'status' => $feedbackCase->status === 'new' ? 'reviewing' : $feedbackCase->status]);
            if (!$internal && $feedbackCase->submitted_by_user_id) {
                $this->notifications->notifyUsers([$feedbackCase->submitted_by_user_id], 'feedback_reply', 'Nouvelle réponse à votre demande', $feedbackCase->reference . ' · ' . $feedbackCase->subject, ['feedback_case_id' => $feedbackCase->id, 'school_id' => $feedbackCase->school_id, 'route' => route($this->submitterRoutePrefix($feedbackCase) . '.show', $feedbackCase, absolute: false)]);
            }
        } else {
            $this->notifyManagers($feedbackCase, 'Nouvelle réponse du demandeur', $feedbackCase->reference . ' · ' . $feedbackCase->subject);
        }

        return back()->with('success', 'Votre message a été ajouté.');
    }

    public function update(Request $request, FeedbackCase $feedbackCase)
    {
        $user = $request->user();
        $this->authorizeCase($user, $feedbackCase);
        abort_unless($this->canManage($user), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(FeedbackCase::statuses()))],
            'priority' => ['required', Rule::in(array_keys(FeedbackCase::priorities()))],
            'assigned_to_user_id' => ['nullable', 'integer'],
        ]);
        if (!empty($data['assigned_to_user_id'])) {
            User::query()->where('school_id', $feedbackCase->school_id)->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE])->findOrFail($data['assigned_to_user_id']);
        }
        if ($data['status'] === 'resolved') {
            $data['resolved_at'] = now();
        }
        if ($data['status'] === 'closed') {
            $data['closed_at'] = now();
            $data['closed_by_user_id'] = $user->id;
        }
        $feedbackCase->update($data);
        if ($feedbackCase->submitted_by_user_id) {
            $this->notifications->notifyUsers([$feedbackCase->submitted_by_user_id], 'feedback_status', 'Mise à jour de votre demande', $feedbackCase->reference . ' : ' . FeedbackCase::statuses()[$feedbackCase->status], ['feedback_case_id' => $feedbackCase->id, 'school_id' => $feedbackCase->school_id, 'route' => route($this->submitterRoutePrefix($feedbackCase) . '.show', $feedbackCase, absolute: false)]);
        }

        return back()->with('success', 'Le suivi a été mis à jour.');
    }

    private function notifyManagers(FeedbackCase $case, string $title, string $body): void
    {
        foreach ([User::ROLE_ADMIN => 'admin', User::ROLE_SCHOOL_LIFE => 'school-life'] as $role => $prefix) {
            $ids = User::query()->where('school_id', $case->school_id)->where('role', $role)->pluck('id')->all();
            $this->notifications->notifyUsers($ids, 'feedback_case', $title, $body, ['feedback_case_id' => $case->id, 'school_id' => $case->school_id, 'route' => route($prefix . '.feedback-cases.show', $case, absolute: false)]);
        }
    }

    private function authorizeCase(User $user, FeedbackCase $case): void
    {
        abort_unless((int) $case->school_id === $this->schoolId($user), 404);
        if (!$this->canManage($user) && (string) $user->role !== User::ROLE_DIRECTOR) {
            abort_unless((int) $case->submitted_by_user_id === (int) $user->id, 404);
        }
    }

    private function canManage(User $user): bool
    {
        return in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE], true);
    }

    private function schoolId(User $user): int
    {
        $id = app()->bound('current_school_id') ? (int) app('current_school_id') : (int) $user->school_id;
        abort_unless($id > 0, 403);
        return $id;
    }

    private function routePrefix(User $user): string
    {
        return match ((string) $user->role) {
            User::ROLE_ADMIN => 'admin.feedback-cases',
            User::ROLE_SCHOOL_LIFE => 'school-life.feedback-cases',
            User::ROLE_DIRECTOR => 'director.feedback-cases',
            User::ROLE_TEACHER => 'teacher.feedback-cases',
            default => 'parent.feedback-cases',
        };
    }

    private function submitterRoutePrefix(FeedbackCase $case): string
    {
        $role = (string) ($case->submitter?->role ?? User::ROLE_PARENT);
        return $role === User::ROLE_TEACHER ? 'teacher.feedback-cases' : 'parent.feedback-cases';
    }

    private function viewData(User $user, array $data): array
    {
        $layout = match ((string) $user->role) {
            User::ROLE_ADMIN => 'admin-layout',
            User::ROLE_SCHOOL_LIFE => 'school-life-layout',
            User::ROLE_DIRECTOR => 'director-layout',
            User::ROLE_TEACHER => 'teacher-layout',
            default => 'parent-layout',
        };
        return $data + ['routePrefix' => $this->routePrefix($user), 'layoutComponent' => $layout, 'canCreate' => !in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE, User::ROLE_DIRECTOR], true)];
    }
}
