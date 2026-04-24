<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Homework;
use App\Models\HomeworkAttachment;
use App\Models\Subject;
use App\Models\User;
use App\Services\HomeworkAttachmentStorageService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class HomeworkController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $this->schoolId();

        $q = trim((string) $request->get('q', ''));
        $statusFilter = self::normalizeStatus((string) $request->get('status', 'all'));
        $classroomId = (int) $request->get('classroom_id', 0);
        $dateFilter = trim((string) $request->get('date', ''));
        if ($dateFilter !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) {
            $dateFilter = '';
        }
        $hasStatusColumn = $this->hasStatusColumn();

        $homeworks = Homework::query()
            ->where('school_id', $schoolId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhereHas('classroom', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($classroomId > 0, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($dateFilter !== '', function ($query) use ($dateFilter) {
                $query->whereDate('created_at', $dateFilter);
            })
            ->when($statusFilter !== 'all' && $hasStatusColumn, function ($query) use ($statusFilter) {
                $this->applyNormalizedStatusFilter($query, $statusFilter);
            })
            ->with(['classroom.level', 'teacher', 'subject', 'attachments'])
            ->withCount('attachments')
            ->when($hasStatusColumn, function ($query) {
                $query->orderByRaw("CASE
                    WHEN LOWER(COALESCE(status, '')) IN ('pending','draft','') THEN 0
                    WHEN LOWER(COALESCE(status, '')) IN ('approved','confirmed') THEN 1
                    ELSE 2
                END");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statsBase = Homework::query()->where('school_id', $schoolId);
        $stats = [
            'pending' => $hasStatusColumn ? (clone $statsBase)->pending()->count() : 0,
            'approved' => $hasStatusColumn ? (clone $statsBase)->approved()->count() : 0,
            'rejected' => $hasStatusColumn
                ? (clone $statsBase)->whereIn('status', ['rejected', 'cancelled', 'archived'])->count()
                : 0,
            'this_week' => (clone $statsBase)->where('created_at', '>=', Carbon::now()->subDays(7))->count(),
        ];

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view($this->viewPrefix() . '.index', [
            'homeworks' => $homeworks,
            'q' => $q,
            'statusFilter' => $statusFilter,
            'stats' => $stats,
            'classroomId' => $classroomId,
            'dateFilter' => $dateFilter,
            'classrooms' => $classrooms,
            'routePrefix' => $this->routePrefix(),
            'canCreate' => $this->canCreate(),
            'portalTitle' => $this->portalTitle(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canCreate(), 403);
        $schoolId = $this->schoolId();

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->with('level:id,name,school_id')
            ->orderBy('name')
            ->get();

        $subjects = Subject::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view($this->viewPrefix() . '.create', [
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'routePrefix' => $this->routePrefix(),
            'portalTitle' => $this->portalTitle(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->canCreate(), 403);
        $schoolId = $this->schoolId();

        $data = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ]);

        $classroomExists = Classroom::query()
            ->where('id', (int) $data['classroom_id'])
            ->where('school_id', $schoolId)
            ->exists();

        if (!$classroomExists) {
            return back()->withErrors([
                'classroom_id' => 'La classe selectionnee est invalide pour cette ecole.',
            ])->withInput();
        }

        $subjectId = (int) ($data['subject_id'] ?? 0);
        if ($subjectId > 0) {
            $subjectExists = Subject::query()
                ->where('school_id', $schoolId)
                ->whereKey($subjectId)
                ->exists();

            if (!$subjectExists) {
                return back()->withErrors([
                    'subject_id' => 'La matiere selectionnee est invalide pour cette ecole.',
                ])->withInput();
            }
        }

        $payload = [
            'school_id' => $schoolId,
            'classroom_id' => (int) $data['classroom_id'],
            'teacher_id' => auth()->id(),
            'subject_id' => $subjectId > 0 ? $subjectId : null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
        ];
        if ($this->hasColumn('status')) {
            $payload['status'] = 'pending';
        }

        Homework::create($payload);

        return redirect()->route($this->routePrefix() . '.index')->with('success', 'Devoir ajoute.');
    }

    public function edit(Homework $homework)
    {
        $schoolId = $this->schoolId();
        abort_unless((int) ($homework->school_id ?? 0) === $schoolId, 404);

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->with('level:id,name,school_id')
            ->orderBy('name')
            ->get();

        $subjects = Subject::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view($this->viewPrefix() . '.edit', [
            'homework' => $homework->load(['classroom.level', 'subject', 'attachments']),
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'routePrefix' => $this->routePrefix(),
            'portalTitle' => $this->portalTitle(),
        ]);
    }

    public function update(Request $request, Homework $homework)
    {
        $schoolId = $this->schoolId();
        abort_unless((int) ($homework->school_id ?? 0) === $schoolId, 404);

        $data = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ]);

        $classroomExists = Classroom::query()
            ->where('id', (int) $data['classroom_id'])
            ->where('school_id', $schoolId)
            ->exists();

        if (!$classroomExists) {
            return back()->withErrors([
                'classroom_id' => 'La classe selectionnee est invalide pour cette ecole.',
            ])->withInput();
        }

        $subjectId = (int) ($data['subject_id'] ?? 0);
        if ($subjectId > 0) {
            $subjectExists = Subject::query()
                ->where('school_id', $schoolId)
                ->whereKey($subjectId)
                ->exists();

            if (!$subjectExists) {
                return back()->withErrors([
                    'subject_id' => 'La matiere selectionnee est invalide pour cette ecole.',
                ])->withInput();
            }
        }

        $homework->update([
            'classroom_id' => (int) $data['classroom_id'],
            'subject_id' => $subjectId > 0 ? $subjectId : null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
        ]);

        return redirect()
            ->route($this->routePrefix() . '.show', $homework)
            ->with('success', 'Le devoir a ete mis a jour.');
    }

    public function destroy(Homework $homework)
    {
        $schoolId = $this->schoolId();
        abort_unless((int) ($homework->school_id ?? 0) === $schoolId, 404);

        $homework->delete();

        return redirect()
            ->route($this->routePrefix() . '.index')
            ->with('success', 'Le devoir a ete supprime.');
    }

    public function approve(Homework $homework)
    {
        $schoolId = $this->schoolId();

        abort_unless((int) ($homework->school_id ?? 0) === $schoolId, 404);

        $payload = [];
        if ($this->hasColumn('status')) {
            $payload['status'] = 'approved';
        }
        if ($this->hasColumn('approved_at')) {
            $payload['approved_at'] = now();
        }
        if ($this->hasColumn('approved_by')) {
            $payload['approved_by'] = auth()->id();
        }
        if ($this->hasColumn('rejected_at')) {
            $payload['rejected_at'] = null;
        }
        if ($this->hasColumn('rejected_by')) {
            $payload['rejected_by'] = null;
        }
        if ($payload !== []) {
            $homework->update($payload);
        }

        try {
            $service = app(NotificationService::class);
            $parentIds = $service->parentIdsByClassroom((int) $homework->classroom_id, $schoolId);
            $studentIds = $service->studentUserIdsByClassroom((int) $homework->classroom_id, $schoolId);
            $teacherIds = array_filter([(int) ($homework->teacher_id ?? 0)]);

            $service->notifyUsers(
                array_values(array_unique(array_merge($parentIds, $studentIds, $teacherIds))),
                'homework',
                'Devoir approuve',
                (string) ($homework->title ?: 'Un devoir a ete valide pour votre classe.'),
                [
                    'homework_id' => (int) $homework->id,
                    'classroom_id' => (int) $homework->classroom_id,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Homework approval notification failed', [
                'homework_id' => $homework->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Devoir approuve.');
    }

    public function reject(Homework $homework)
    {
        $schoolId = $this->schoolId();

        abort_unless((int) ($homework->school_id ?? 0) === $schoolId, 404);

        $payload = [];
        if ($this->hasColumn('status')) {
            $payload['status'] = 'rejected';
        }
        if ($this->hasColumn('rejected_at')) {
            $payload['rejected_at'] = now();
        }
        if ($this->hasColumn('rejected_by')) {
            $payload['rejected_by'] = auth()->id();
        }
        if ($this->hasColumn('approved_at')) {
            $payload['approved_at'] = null;
        }
        if ($this->hasColumn('approved_by')) {
            $payload['approved_by'] = null;
        }
        if ($payload !== []) {
            $homework->update($payload);
        }

        $teacherId = (int) ($homework->teacher_id ?? 0);
        if ($teacherId > 0) {
            try {
                app(NotificationService::class)->notifyUsers(
                    [$teacherId],
                    'homework',
                    'Devoir refuse',
                    (string) ($homework->title ?: 'Votre devoir a ete refuse.'),
                    ['homework_id' => (int) $homework->id, 'url' => route('teacher.homeworks.index')]
                );
            } catch (\Throwable $e) {
                Log::warning('Homework rejection notification failed', [
                    'homework_id' => $homework->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Devoir rejete.');
    }

    public function show(Homework $homework)
    {
        $schoolId = $this->schoolId();

        abort_unless((int) ($homework->school_id ?? 0) === $schoolId, 404);
        $homework->load(['classroom.level', 'teacher:id,name,email', 'subject', 'attachments']);

        return view($this->viewPrefix() . '.show', [
            'homework' => $homework,
            'routePrefix' => $this->routePrefix(),
            'canCreate' => $this->canCreate(),
            'portalTitle' => $this->portalTitle(),
        ]);
    }

    public function downloadAttachment(HomeworkAttachment $attachment)
    {
        $schoolId = $this->schoolId();

        $attachment->load('homework');

        abort_unless($attachment->homework, 404);
        abort_unless((int) $attachment->school_id === $schoolId, 403);
        abort_unless((int) $attachment->homework->school_id === $schoolId, 404);

        return app(HomeworkAttachmentStorageService::class)->downloadResponse($attachment);
    }

    protected function routePrefix(): string
    {
        return 'admin.homeworks';
    }

    protected function viewPrefix(): string
    {
        return 'admin.homeworks';
    }

    protected function canCreate(): bool
    {
        return true;
    }

    protected function portalTitle(): string
    {
        return auth()->user()?->role === User::ROLE_SCHOOL_LIFE
            ? 'Gestion des devoirs'
            : 'Validation des devoirs';
    }

    protected function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if ($schoolId <= 0) {
            abort(403, 'School context missing.');
        }

        return $schoolId;
    }

    protected function hasColumn(string $column): bool
    {
        return Schema::hasTable('homeworks') && Schema::hasColumn('homeworks', $column);
    }

    protected function hasStatusColumn(): bool
    {
        return $this->hasColumn('status');
    }

    protected static function normalizeStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            '', 'draft', 'pending' => 'pending',
            'confirmed', 'approved' => 'approved',
            'archived', 'cancelled', 'rejected' => 'rejected',
            'all' => 'all',
            default => 'pending',
        };
    }

    protected function applyNormalizedStatusFilter($query, string $status): void
    {
        if ($status === 'pending') {
            $query->pending();
            return;
        }

        if ($status === 'approved') {
            $query->approved();
            return;
        }

        if ($status === 'rejected') {
            $query->whereIn('status', ['rejected', 'cancelled', 'archived']);
        }
    }
}
