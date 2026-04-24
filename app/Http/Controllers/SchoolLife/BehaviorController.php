<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentBehavior;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class BehaviorController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function show(Student $student)
    {
        $student = $this->resolveStudent($student, ['classroom:id,name', 'parentUser:id,name,phone,email']);
        $behaviors = $student->behaviors()->with('author:id,name')->latest('date')->paginate(12);

        return view('school-life.behaviors.show', [
            'student' => $student,
            'behaviors' => $behaviors,
            'types' => StudentBehavior::types(),
        ]);
    }

    public function store(Request $request, Student $student)
    {
        $student = $this->resolveStudent($student);

        $data = $this->validatedData($request);

        $behavior = StudentBehavior::create([
            'school_id' => $this->schoolId(),
            'student_id' => $student->id,
            'type' => $data['type'],
            'description' => $data['description'],
            'visible_to_parent' => (bool) ($data['visible_to_parent'] ?? false),
            'created_by_user_id' => auth()->id(),
            'date' => $data['date'],
        ]);

        if ($student->parent_user_id && (bool) ($data['notify_parent'] ?? true)) {
            $this->notifications->notifyUsers(
                [(int) $student->parent_user_id],
                'school_life',
                'Vie scolaire',
                sprintf('%s : %s', ucfirst($behavior->type), $behavior->description),
                [
                    'student_id' => $student->id,
                    'route' => route('parent.children.index', absolute: false),
                ]
            );
        }

        return back()->with('success', 'Remarque ajoutee.');
    }

    public function update(Request $request, Student $student, StudentBehavior $behavior)
    {
        $student = $this->resolveStudent($student);
        $behavior = $this->resolveBehavior($behavior, $student);
        $data = $this->validatedData($request);

        $behavior->update([
            'type' => $data['type'],
            'description' => $data['description'],
            'visible_to_parent' => (bool) ($data['visible_to_parent'] ?? false),
            'date' => $data['date'],
        ]);

        return back()->with('success', 'Remarque mise a jour.');
    }

    public function destroy(Student $student, StudentBehavior $behavior)
    {
        $student = $this->resolveStudent($student);
        $behavior = $this->resolveBehavior($behavior, $student);
        $behavior->delete();

        return back()->with('success', 'Remarque supprimee.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:' . implode(',', StudentBehavior::types())],
            'description' => ['required', 'string', 'max:1000'],
            'date' => ['required', 'date'],
            'visible_to_parent' => ['nullable', 'boolean'],
            'notify_parent' => ['nullable', 'boolean'],
        ]);
    }

    private function resolveBehavior(StudentBehavior $behavior, Student $student): StudentBehavior
    {
        abort_unless(
            (int) $behavior->school_id === $this->schoolId()
            && (int) $behavior->student_id === (int) $student->id,
            404
        );

        return $behavior;
    }

    private function resolveStudent(Student $student, array $with = []): Student
    {
        return Student::query()
            ->with($with)
            ->where('school_id', $this->schoolId())
            ->findOrFail($student->id);
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
