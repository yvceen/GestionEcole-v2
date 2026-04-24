<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Classroom;
use App\Models\Event;
use App\Models\News;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function adminIndex(Request $request)
    {
        return view('admin.events.index', $this->buildAgendaData($request, true));
    }

    public function teacherIndex(Request $request)
    {
        $this->abortUnlessRole(User::ROLE_TEACHER);

        return view('teacher.events.index', $this->buildAgendaData($request, false));
    }

    public function schoolLifeIndex(Request $request)
    {
        $this->abortUnlessRole(User::ROLE_SCHOOL_LIFE);

        return view('school-life.events.index', $this->buildAgendaData($request, false));
    }

    public function parentIndex(Request $request)
    {
        $this->abortUnlessRole(User::ROLE_PARENT);

        return view('parent.events.index', $this->buildAgendaData($request, false));
    }

    public function studentIndex(Request $request)
    {
        $this->abortUnlessRole(User::ROLE_STUDENT);

        return view('student.events.index', $this->buildAgendaData($request, false));
    }

    public function directorIndex(Request $request)
    {
        $this->abortUnlessRole(User::ROLE_DIRECTOR);

        return view('director.events.index', $this->buildAgendaData($request, false));
    }

    public function feed(Request $request): JsonResponse
    {
        $this->abortUnlessReadableRole();

        $schoolId = $this->schoolId();
        $user = auth()->user();
        $classroomId = $request->integer('classroom_id');
        $teacherId = $request->integer('teacher_id');
        $start = $request->get('start');
        $end = $request->get('end');
        $accessibleClassroomIds = $this->accessibleClassroomIds($user, $schoolId);
        $restrictByClassroom = $this->shouldRestrictAgendaToClassrooms($user);
        $teacherScoped = $user && (string) $user->role === User::ROLE_TEACHER;

        $events = Event::query()
            ->where('school_id', $schoolId)
            ->when($restrictByClassroom, function ($query) use ($accessibleClassroomIds, $teacherScoped, $user): void {
                $query->where(function ($scope) use ($accessibleClassroomIds, $teacherScoped, $user): void {
                    $scope->whereNull('classroom_id');
                    if ($accessibleClassroomIds !== []) {
                        $scope->orWhereIn('classroom_id', $accessibleClassroomIds);
                    }
                    if ($teacherScoped && $user) {
                        $scope->orWhere('teacher_id', (int) $user->id);
                    }
                });
            })
            ->when($classroomId > 0, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($teacherId > 0, fn ($query) => $query->where('teacher_id', $teacherId))
            ->when($start, fn ($query) => $query->where('end', '>=', $start))
            ->when($end, fn ($query) => $query->where('start', '<=', $end))
            ->with(['classroom:id,name', 'teacher:id,name'])
            ->orderBy('start')
            ->get()
            ->map(function (Event $event) {
                $classroomName = $event->classroom?->name ?? 'Classe non renseignee';
                $teacherName = $event->teacher?->name ?? 'Sans enseignant';

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start?->toIso8601String(),
                    'end' => $event->end?->toIso8601String(),
                    'backgroundColor' => $event->color ?: Event::defaultColorForType((string) $event->type),
                    'borderColor' => $event->color ?: Event::defaultColorForType((string) $event->type),
                    'extendedProps' => [
                        'type' => Event::labelForType((string) $event->type),
                        'classroom' => $classroomName,
                        'teacher' => $teacherName,
                    ],
                ];
            })
            ->values();

        $activities = Activity::query()
            ->where('school_id', $schoolId)
            ->when($restrictByClassroom, function ($query) use ($accessibleClassroomIds, $teacherScoped, $user): void {
                $query->where(function ($scope) use ($accessibleClassroomIds, $teacherScoped, $user): void {
                    $scope->whereNull('classroom_id');
                    if ($accessibleClassroomIds !== []) {
                        $scope->orWhereIn('classroom_id', $accessibleClassroomIds);
                    }
                    if ($teacherScoped && $user) {
                        $scope->orWhere('teacher_id', (int) $user->id);
                    }
                });
            })
            ->when($classroomId > 0, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($teacherId > 0, fn ($query) => $query->where('teacher_id', $teacherId))
            ->when($start, fn ($query) => $query->where('end_date', '>=', $start))
            ->when($end, fn ($query) => $query->where('start_date', '<=', $end))
            ->with(['classroom:id,name', 'teacher:id,name'])
            ->orderBy('start_date')
            ->get()
            ->map(function (Activity $activity) {
                return [
                    'id' => 'activity-' . $activity->id,
                    'title' => $activity->title,
                    'start' => $activity->start_date?->toIso8601String(),
                    'end' => $activity->end_date?->toIso8601String(),
                    'backgroundColor' => $activity->color ?: Activity::defaultColorForType((string) $activity->type),
                    'borderColor' => $activity->color ?: Activity::defaultColorForType((string) $activity->type),
                    'extendedProps' => [
                        'type' => 'Activite - ' . Activity::labelForType((string) $activity->type),
                        'classroom' => $activity->classroom?->name ?? 'Classe non renseignee',
                        'teacher' => $activity->teacher?->name ?? 'Sans enseignant',
                    ],
                ];
            })
            ->values();

        return response()->json($events->concat($activities)->sortBy('start')->values());
    }

    public function create()
    {
        $this->abortUnlessRole(User::ROLE_ADMIN);

        return view('admin.events.create', $this->formData(new Event()));
    }

    public function store(Request $request)
    {
        $this->abortUnlessRole(User::ROLE_ADMIN);
        $data = $this->validatedData($request);
        $data['school_id'] = $this->schoolId();
        $data['color'] = $data['color'] ?: Event::defaultColorForType((string) $data['type']);

        $event = Event::create($data);
        $this->syncAgendaNews($event);
        $this->notifyAgendaChange($event, 'create');

        return redirect()->route('admin.events.index')->with('success', 'Evenement agenda cree.');
    }

    public function edit(Event $event)
    {
        $this->abortUnlessRole(User::ROLE_ADMIN);
        $event = $this->resolveSchoolEvent($event);

        return view('admin.events.edit', $this->formData($event));
    }

    public function update(Request $request, Event $event)
    {
        $this->abortUnlessRole(User::ROLE_ADMIN);
        $event = $this->resolveSchoolEvent($event);
        $data = $this->validatedData($request);
        $data['color'] = $data['color'] ?: Event::defaultColorForType((string) $data['type']);
        $event->fill($data);
        $shouldNotify = $event->isDirty(['title', 'type', 'start', 'end', 'classroom_id', 'teacher_id']);
        $event->save();
        $this->syncAgendaNews($event);
        if ($shouldNotify) {
            $this->notifyAgendaChange($event, 'update');
        }

        return redirect()->route('admin.events.index')->with('success', 'Evenement agenda mis a jour.');
    }

    public function destroy(Event $event)
    {
        $this->abortUnlessRole(User::ROLE_ADMIN);
        $event = $this->resolveSchoolEvent($event);
        $this->deleteAgendaNews($event);
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Evenement agenda supprime.');
    }

    private function buildAgendaData(Request $request, bool $canManage): array
    {
        $schoolId = $this->schoolId();
        $user = auth()->user();
        $classroomId = $request->integer('classroom_id');
        $teacherId = $request->integer('teacher_id');
        $accessibleClassroomIds = $this->accessibleClassroomIds($user, $schoolId);
        $restrictByClassroom = !$canManage && $this->shouldRestrictAgendaToClassrooms($user);

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->when($restrictByClassroom, fn ($query) => $query->whereIn('id', $accessibleClassroomIds ?: [-1]))
            ->orderBy('name')
            ->get(['id', 'name']);

        $teachers = User::query()
            ->where('school_id', $schoolId)
            ->where('role', User::ROLE_TEACHER)
            ->when($user && (string) $user->role === User::ROLE_TEACHER && !$canManage, fn ($query) => $query->where('id', (int) $user->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $upcomingEvents = Event::query()
            ->where('school_id', $schoolId)
            ->when($restrictByClassroom, function ($query) use ($accessibleClassroomIds): void {
                $query->where(function ($scope) use ($accessibleClassroomIds): void {
                    $scope->whereNull('classroom_id');
                    if ($accessibleClassroomIds !== []) {
                        $scope->orWhereIn('classroom_id', $accessibleClassroomIds);
                    }
                });
            })
            ->when($classroomId > 0, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($teacherId > 0, fn ($query) => $query->where('teacher_id', $teacherId))
            ->where('end', '>=', now()->subDay())
            ->with(['classroom:id,name', 'teacher:id,name'])
            ->orderBy('start')
            ->limit(8)
            ->get();

        $summary = [
            'total' => Event::query()->where('school_id', $schoolId)->count() + Activity::query()->where('school_id', $schoolId)->count(),
            'course' => Event::query()->where('school_id', $schoolId)->where('type', Event::TYPE_COURSE)->count(),
            'exam' => Event::query()->where('school_id', $schoolId)->where('type', Event::TYPE_EXAM)->count(),
            'activity' => Event::query()->where('school_id', $schoolId)->where('type', Event::TYPE_ACTIVITY)->count()
                + Activity::query()->where('school_id', $schoolId)->count(),
        ];

        return compact('classrooms', 'teachers', 'classroomId', 'teacherId', 'canManage', 'summary', 'upcomingEvents');
    }

    private function formData(Event $event): array
    {
        $schoolId = $this->schoolId();

        return [
            'event' => $event,
            'types' => Event::types(),
            'classrooms' => Classroom::query()
                ->where('school_id', $schoolId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'teachers' => User::query()
                ->where('school_id', $schoolId)
                ->where('role', User::ROLE_TEACHER)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    private function validatedData(Request $request): array
    {
        $schoolId = $this->schoolId();

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', Event::types())],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'classroom_id' => [
                'nullable',
                'integer',
                Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('school_id', $schoolId)
                    ->where('role', User::ROLE_TEACHER)),
            ],
            'color' => ['nullable', 'string', 'max:24'],
        ]);
    }

    private function resolveSchoolEvent(Event $event): Event
    {
        abort_unless((int) $event->school_id === $this->schoolId(), 404);

        return $event;
    }

    private function abortUnlessReadableRole(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless(in_array((string) $user->role, [
            User::ROLE_ADMIN,
            User::ROLE_TEACHER,
            User::ROLE_SCHOOL_LIFE,
            User::ROLE_DIRECTOR,
            User::ROLE_PARENT,
            User::ROLE_STUDENT,
        ], true), 403);
    }

    private function abortUnlessRole(string $role): void
    {
        $user = auth()->user();
        abort_unless($user && (string) $user->role === $role, 403);
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }

    private function syncAgendaNews(Event $event): void
    {
        News::query()->updateOrCreate(
            [
                'school_id' => (int) $event->school_id,
                'source_type' => 'agenda_event',
                'source_id' => (int) $event->id,
            ],
            [
                'scope' => $event->classroom_id ? 'classroom' : 'school',
                'classroom_id' => $event->classroom_id ?: null,
                'title' => 'Agenda: ' . $event->title,
                'status' => 'published',
                'date' => optional($event->start)->toDateString() ?: now()->toDateString(),
            ]
        );
    }

    private function deleteAgendaNews(Event $event): void
    {
        News::query()
            ->where('school_id', (int) $event->school_id)
            ->where('source_type', 'agenda_event')
            ->where('source_id', (int) $event->id)
            ->delete();
    }

    private function notifyAgendaChange(Event $event, string $action): void
    {
        $schoolId = (int) $event->school_id;
        $classroomId = (int) ($event->classroom_id ?? 0);

        $recipientIds = $classroomId > 0
            ? array_merge(
                $this->notifications->parentIdsByClassroom($classroomId, $schoolId),
                $this->notifications->studentUserIdsByClassroom($classroomId, $schoolId),
                $this->notifications->teacherIdsByClassroom($classroomId, $schoolId)
            )
            : array_merge(
                $this->notifications->parentIdsBySchool($schoolId),
                $this->notifications->studentUserIdsBySchool($schoolId),
                $this->notifications->teacherIdsBySchool($schoolId)
            );

        $recipientIds = array_merge(
            $recipientIds,
            User::query()
                ->where('school_id', $schoolId)
                ->whereIn('role', [User::ROLE_DIRECTOR, User::ROLE_SCHOOL_LIFE])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );

        $recipientIds = array_values(array_unique(array_map('intval', $recipientIds)));
        if ($recipientIds === []) {
            return;
        }

        $title = $action === 'update'
            ? 'Agenda mis a jour'
            : 'Nouvel evenement ajoute a l agenda';
        $body = $event->title . ' - ' . (optional($event->start)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'));

        $this->notifications->notifyUsers($recipientIds, 'agenda', $title, $body, [
            'event_id' => (int) $event->id,
            'classroom_id' => $classroomId ?: null,
            'school_id' => $schoolId,
        ]);
    }

    private function accessibleClassroomIds(?User $user, int $schoolId): array
    {
        if (!$user) {
            return [];
        }

        return match ((string) $user->role) {
            User::ROLE_PARENT => Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('parent_user_id', (int) $user->id)
                ->whereNotNull('classroom_id')
                ->pluck('classroom_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all(),
            User::ROLE_STUDENT => Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('user_id', (int) $user->id)
                ->whereNotNull('classroom_id')
                ->pluck('classroom_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all(),
            User::ROLE_TEACHER => $user->teacherClassrooms()
                ->wherePivot('school_id', $schoolId)
                ->pluck('classrooms.id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all(),
            default => [],
        };
    }

    private function shouldRestrictAgendaToClassrooms(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array((string) $user->role, [User::ROLE_PARENT, User::ROLE_STUDENT, User::ROLE_TEACHER], true);
    }
}
