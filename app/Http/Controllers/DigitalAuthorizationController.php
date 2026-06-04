<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\DigitalAuthorization;
use App\Models\DigitalAuthorizationRecipient;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DigitalAuthorizationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($user);
        $status = (string) $request->get('status', '');
        $q = trim((string) $request->get('q', ''));

        $recipientScope = fn (Builder $query) => (string) $user->role === User::ROLE_PARENT
            ? $query->where('parent_user_id', $user->id)
            : $query;

        $query = DigitalAuthorization::query()
            ->where('school_id', $schoolId)
            ->with('createdBy:id,name')
            ->withCount([
                'recipients' => $recipientScope,
                'recipients as pending_count' => fn ($query) => $recipientScope($query)->where('status', DigitalAuthorizationRecipient::STATUS_PENDING),
                'recipients as approved_count' => fn ($query) => $recipientScope($query)->where('status', DigitalAuthorizationRecipient::STATUS_APPROVED),
                'recipients as declined_count' => fn ($query) => $recipientScope($query)->where('status', DigitalAuthorizationRecipient::STATUS_DECLINED),
            ])
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->when($status !== '', fn ($query) => $query->where('status', $status));

        if ((string) $user->role === User::ROLE_PARENT) {
            $query->whereHas('recipients', fn ($query) => $query->where('parent_user_id', $user->id));
        }

        $authorizations = $query->latest('published_at')->latest()->paginate(16)->withQueryString();
        $statsQuery = DigitalAuthorizationRecipient::query()->where('school_id', $schoolId);
        if ((string) $user->role === User::ROLE_PARENT) {
            $statsQuery->where('parent_user_id', $user->id);
        }
        $stats = [
            'pending' => (clone $statsQuery)->where('status', DigitalAuthorizationRecipient::STATUS_PENDING)->count(),
            'approved' => (clone $statsQuery)->where('status', DigitalAuthorizationRecipient::STATUS_APPROVED)->count(),
            'declined' => (clone $statsQuery)->where('status', DigitalAuthorizationRecipient::STATUS_DECLINED)->count(),
        ];

        return view('digital-authorizations.index', $this->viewData($user, compact('authorizations', 'stats', 'q', 'status')));
    }

    public function create(Request $request)
    {
        $this->authorizeManager($request->user());
        $schoolId = $this->schoolId($request->user());
        $classrooms = Classroom::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $students = Student::query()->active()->where('school_id', $schoolId)->with('classroom:id,name')->whereNotNull('parent_user_id')->orderBy('full_name')->get(['id', 'full_name', 'classroom_id']);

        return view('digital-authorizations.create', $this->viewData($request->user(), compact('classrooms', 'students')));
    }

    public function store(Request $request)
    {
        $this->authorizeManager($request->user());
        $schoolId = $this->schoolId($request->user());
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:' . implode(',', array_keys(DigitalAuthorization::categories()))],
            'description' => ['required', 'string', 'max:10000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'event_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'requires_comment' => ['nullable', 'boolean'],
            'target_type' => ['required', 'in:all,classroom,students'],
            'classroom_id' => ['nullable', 'integer'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
        ]);

        $students = Student::query()->active()->where('school_id', $schoolId)->whereNotNull('parent_user_id');
        if ($data['target_type'] === 'classroom') {
            $students->where('classroom_id', (int) ($data['classroom_id'] ?? 0));
        } elseif ($data['target_type'] === 'students') {
            $students->whereIn('id', $data['student_ids'] ?? []);
        }
        $recipients = $students->get(['id', 'parent_user_id']);
        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'target_type' => 'Aucun élève avec un parent associé ne correspond à cette sélection.',
            ]);
        }

        $authorization = DB::transaction(function () use ($data, $schoolId, $request, $recipients) {
            $authorization = DigitalAuthorization::create([
                'school_id' => $schoolId,
                'created_by_user_id' => $request->user()->id,
                'title' => $data['title'],
                'category' => $data['category'],
                'description' => $data['description'],
                'instructions' => $data['instructions'] ?? null,
                'event_at' => $data['event_at'] ?? null,
                'due_at' => $data['due_at'] ?? null,
                'requires_comment' => (bool) ($data['requires_comment'] ?? false),
                'status' => DigitalAuthorization::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            $authorization->recipients()->createMany($recipients->map(fn (Student $student) => [
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'parent_user_id' => $student->parent_user_id,
                'status' => DigitalAuthorizationRecipient::STATUS_PENDING,
            ])->all());

            return $authorization;
        });

        $this->notifications->notifyParents(
            $recipients->pluck('parent_user_id')->map(fn ($id) => (int) $id)->unique()->values()->all(),
            'digital_authorization',
            'Nouvelle autorisation à valider',
            $authorization->title,
            [
                'digital_authorization_id' => $authorization->id,
                'school_id' => $schoolId,
                'route' => route('parent.digital-authorizations.show', $authorization, absolute: false),
            ],
        );

        return redirect()->route($this->routePrefix($request->user()) . '.show', $authorization)
            ->with('success', 'La demande d’autorisation a été envoyée aux parents.');
    }

    public function show(Request $request, DigitalAuthorization $digitalAuthorization)
    {
        $user = $request->user();
        $this->authorizeView($user, $digitalAuthorization);
        $digitalAuthorization->load('createdBy:id,name');
        $recipients = $digitalAuthorization->recipients()
            ->with(['student.classroom:id,name', 'parentUser:id,name,phone'])
            ->when((string) $user->role === User::ROLE_PARENT, fn ($query) => $query->where('parent_user_id', $user->id))
            ->orderByRaw("FIELD(status, 'pending', 'declined', 'approved')")
            ->orderBy('id')
            ->get();

        return view('digital-authorizations.show', $this->viewData($user, compact('digitalAuthorization', 'recipients')));
    }

    public function close(Request $request, DigitalAuthorization $digitalAuthorization)
    {
        $this->authorizeManager($request->user());
        $this->authorizeView($request->user(), $digitalAuthorization);
        $digitalAuthorization->update(['status' => DigitalAuthorization::STATUS_CLOSED, 'closed_at' => now()]);

        return back()->with('success', 'La demande est maintenant clôturée.');
    }

    public function respond(Request $request, DigitalAuthorizationRecipient $recipient)
    {
        $user = $request->user();
        abort_unless((string) $user->role === User::ROLE_PARENT && (int) $recipient->parent_user_id === (int) $user->id, 403);
        $recipient->load('authorization');
        abort_unless((int) $recipient->school_id === $this->schoolId($user), 404);
        abort_if($recipient->authorization->status === DigitalAuthorization::STATUS_CLOSED, 422, 'Cette demande est clôturée.');
        abort_if($recipient->authorization->due_at?->isPast(), 422, 'La date limite de réponse est dépassée.');

        $data = $request->validate([
            'decision' => ['required', 'in:approved,declined'],
            'signed_name' => ['required', 'string', 'max:255'],
            'response_comment' => [$recipient->authorization->requires_comment ? 'required' : 'nullable', 'string', 'max:3000'],
            'confirmation' => ['accepted'],
        ]);

        $recipient->update([
            'status' => $data['decision'],
            'signed_name' => trim($data['signed_name']),
            'response_comment' => trim((string) ($data['response_comment'] ?? '')) ?: null,
            'responded_at' => now(),
            'response_ip' => $request->ip(),
            'response_user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
        ]);

        foreach ([User::ROLE_ADMIN => 'admin', User::ROLE_SCHOOL_LIFE => 'school-life'] as $role => $prefix) {
            $managerIds = User::query()->where('school_id', $recipient->school_id)->where('role', $role)->pluck('id')->all();
            $this->notifications->notifyUsers($managerIds, 'digital_authorization_response', 'Réponse reçue', $recipient->student?->full_name . ' : ' . $recipient->authorization->title, [
                'digital_authorization_response_id' => $recipient->digital_authorization_id,
                'school_id' => $recipient->school_id,
                'route' => route($prefix . '.digital-authorizations.show', $recipient->authorization, absolute: false),
            ]);
        }

        return back()->with('success', 'Votre réponse a été enregistrée.');
    }

    private function authorizeManager(User $user): void
    {
        abort_unless(in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE], true), 403);
    }

    private function authorizeView(User $user, DigitalAuthorization $authorization): void
    {
        abort_unless((int) $authorization->school_id === $this->schoolId($user), 404);
        if ((string) $user->role === User::ROLE_PARENT) {
            abort_unless($authorization->recipients()->where('parent_user_id', $user->id)->exists(), 404);
        }
    }

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : (int) $user->school_id;
        abort_unless($schoolId > 0, 403);
        return $schoolId;
    }

    private function routePrefix(User $user): string
    {
        return match ((string) $user->role) {
            User::ROLE_ADMIN => 'admin.digital-authorizations',
            User::ROLE_SCHOOL_LIFE => 'school-life.digital-authorizations',
            User::ROLE_DIRECTOR => 'director.digital-authorizations',
            default => 'parent.digital-authorizations',
        };
    }

    private function viewData(User $user, array $data): array
    {
        return $data + [
            'layoutComponent' => match ((string) $user->role) {
                User::ROLE_ADMIN => 'admin-layout',
                User::ROLE_SCHOOL_LIFE => 'school-life-layout',
                User::ROLE_DIRECTOR => 'director-layout',
                default => 'parent-layout',
            },
            'routePrefix' => $this->routePrefix($user),
            'canManage' => in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE], true),
            'isParent' => (string) $user->role === User::ROLE_PARENT,
        ];
    }
}
