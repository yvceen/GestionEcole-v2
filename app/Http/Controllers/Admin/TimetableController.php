<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsTimetableGrid;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Timetable;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\StudentPlacementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    use BuildsTimetableGrid;

    public function __construct(
        private readonly AcademicYearService $academicYears,
        private readonly StudentPlacementService $placements,
    ) {
    }

    private const DAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
    ];

    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'Contexte ecole manquant.');
        }

        $requestedAcademicYearId = $request->integer('academic_year_id') ?: null;
        $academicYear = $this->academicYears->resolveYearForSchool($schoolId, $requestedAcademicYearId);

        $classrooms = Classroom::query()
            ->where('is_active', true)
            ->with('level')
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get()
            ->when($this->placements->supportsPlacements(), function ($collection) use ($schoolId, $academicYear) {
                $classroomIds = \App\Models\StudentAcademicYear::query()
                    ->where('school_id', $schoolId)
                    ->where('academic_year_id', $academicYear->id)
                    ->whereNotNull('classroom_id')
                    ->distinct()
                    ->pluck('classroom_id')
                    ->all();

                return empty($classroomIds) ? $collection : $collection->whereIn('id', $classroomIds)->values();
            });

        $settings = $this->loadTimetableSetting($schoolId);

        $selectedClassroomId = (int) $request->integer('classroom_id');
        if ($selectedClassroomId <= 0) {
            $selectedClassroomId = (int) ($classrooms->first()?->id ?? 0);
        }

        $slots = collect();
        if ($selectedClassroomId > 0) {
            $slots = $this->academicYears->applyYearScope(Timetable::query(), $schoolId, $requestedAcademicYearId)
                ->where('school_id', $schoolId)
                ->where('classroom_id', $selectedClassroomId)
                ->with('teacher:id,name')
                ->orderBy('day')
                ->orderBy('start_time')
                ->get();
        }

        $grid = $this->buildTimetableGridPayload($slots, $settings);

        return view('admin.timetable.index', [
            'classrooms' => $classrooms,
            'selectedClassroomId' => $selectedClassroomId,
            'selectedClass' => $classrooms->firstWhere('id', $selectedClassroomId),
            'settings' => $settings,
            'days' => self::DAYS,
            'slots' => $slots,
            'times' => $grid['times'],
            'slotsByDay' => $grid['slotsByDay'],
            'lunchBlock' => $grid['lunchBlock'],
            'totalMinutes' => $grid['totalMinutes'],
            'currentAcademicYear' => $academicYear,
        ]);
    }

    public function create(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'Contexte ecole manquant.');
        }

        $classrooms = Classroom::query()
            ->where('is_active', true)
            ->with('level')
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $teachers = User::query()
            ->where('school_id', $schoolId)
            ->where('role', User::ROLE_TEACHER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $settings = $this->loadTimetableSetting($schoolId);

        return view('admin.timetable.create', [
            'days' => self::DAYS,
            'classrooms' => $classrooms,
            'teachers' => $teachers,
            'selectedClassroomId' => (int) $request->integer('classroom_id'),
            'settings' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'Contexte ecole manquant.');
        }

        $data = $this->validatePayload($request, $schoolId);
        $this->assertNoOverlap($data, null, $schoolId);

        Timetable::create([
            'school_id' => $schoolId,
            'academic_year_id' => $this->academicYears->requireCurrentYearForSchool($schoolId)->id,
            'classroom_id' => $data['classroom_id'],
            'day' => $data['day'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'subject' => $data['subject'],
            'teacher_id' => $data['teacher_id'] ?? null,
            'room' => $data['room'] ?? null,
        ]);

        return redirect()
            ->route('admin.timetable.index', ['classroom_id' => $data['classroom_id']])
            ->with('success', 'Creneau ajoute avec succes.');
    }

    public function edit(Timetable $timetable)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'Contexte ecole manquant.');
        }
        $this->assertTimetableOwnership($timetable);

        $classrooms = Classroom::query()
            ->where('is_active', true)
            ->with('level')
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $teachers = User::query()
            ->where('school_id', $schoolId)
            ->where('role', User::ROLE_TEACHER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $settings = $this->loadTimetableSetting($schoolId);

        return view('admin.timetable.edit', [
            'timetable' => $timetable,
            'days' => self::DAYS,
            'classrooms' => $classrooms,
            'teachers' => $teachers,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request, Timetable $timetable)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'Contexte ecole manquant.');
        }
        $this->assertTimetableOwnership($timetable);

        $data = $this->validatePayload($request, $schoolId);
        $this->assertNoOverlap($data, (int) $timetable->id, $schoolId);

        $timetable->update([
            'classroom_id' => $data['classroom_id'],
            'day' => $data['day'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'subject' => $data['subject'],
            'teacher_id' => $data['teacher_id'] ?? null,
            'room' => $data['room'] ?? null,
        ]);

        return redirect()
            ->route('admin.timetable.index', ['classroom_id' => $data['classroom_id']])
            ->with('success', 'Creneau modifie avec succes.');
    }

    public function destroy(Timetable $timetable)
    {
        $this->assertTimetableOwnership($timetable);

        $classroomId = (int) $timetable->classroom_id;
        $timetable->delete();

        return redirect()
            ->route('admin.timetable.index', ['classroom_id' => $classroomId])
            ->with('success', 'Creneau supprime.');
    }

    public function updateTimePosition(Request $request, Timetable $timetable)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'Contexte ecole manquant.');
        }

        $data = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ], [
            'start_time.required' => 'L heure de debut est obligatoire.',
            'end_time.required' => 'L heure de fin est obligatoire.',
            'end_time.after' => 'L heure de fin doit etre apres l heure de debut.',
        ]);

        if ((int) $timetable->school_id !== $schoolId) {
            abort(404);
        }

        $settings = $this->loadTimetableSetting($schoolId);
        $dayStart = substr((string) $settings->day_start_time, 0, 5);
        $dayEnd = substr((string) $settings->day_end_time, 0, 5);

        if ($data['start_time'] < $dayStart || $data['end_time'] > $dayEnd) {
            throw ValidationException::withMessages([
                'start_time' => "Le creneau doit etre compris entre {$dayStart} et {$dayEnd}.",
            ]);
        }

        $this->assertNoOverlap([
            'classroom_id' => (int) $timetable->classroom_id,
            'day' => (int) $timetable->day,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ], (int) $timetable->id, $schoolId);

        $timetable->update([
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Creneau mis a jour',
        ]);
    }

    private function validatePayload(Request $request, int $schoolId): array
    {
        return $request->validate([
            'classroom_id' => [
                'required',
                'integer',
                Rule::exists('classrooms', 'id')->where(function ($q) use ($schoolId) {
                    $q->where(function ($w) use ($schoolId) {
                        $w->where('school_id', $schoolId)->orWhereNull('school_id');
                    });
                }),
            ],
            'day' => ['required', 'integer', Rule::in(array_keys(self::DAYS))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'subject' => ['required', 'string', 'max:255'],
            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)->where('role', User::ROLE_TEACHER)),
            ],
            'room' => ['nullable', 'string', 'max:120'],
        ], [
            'classroom_id.required' => 'La classe est obligatoire.',
            'classroom_id.exists' => 'La classe selectionnee est invalide.',
            'day.required' => 'Le jour est obligatoire.',
            'day.in' => 'Le jour selectionne est invalide.',
            'start_time.required' => 'L heure de debut est obligatoire.',
            'end_time.required' => 'L heure de fin est obligatoire.',
            'end_time.after' => 'L heure de fin doit etre apres l heure de debut.',
            'subject.required' => 'La matiere est obligatoire.',
            'teacher_id.exists' => 'L enseignant selectionne est invalide.',
        ]);
    }

    private function assertNoOverlap(array $data, ?int $ignoreId, int $schoolId): void
    {
        $query = Timetable::query()
            ->where('school_id', $schoolId)
            ->where('classroom_id', $data['classroom_id'])
            ->where('day', $data['day'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_time' => 'Ce creneau chevauche un autre creneau de la meme classe.',
            ]);
        }
    }

    private function assertTimetableOwnership(Timetable $timetable): void
    {
        $userSchoolId = (int) (auth()->user()->school_id ?? 0);
        if ($userSchoolId <= 0 || (int) $timetable->school_id !== $userSchoolId) {
            abort(403);
        }
    }
}
