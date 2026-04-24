<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\TeacherPedagogicalResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TeacherPedagogyController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        $teachers = User::where('role', 'teacher')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        $subjects = Subject::where('school_id', $schoolId)->orderBy('name')->get();
        $classrooms = Classroom::where('school_id', $schoolId)->orderBy('name')->get();

        $selectedTeacher = null;
        $assignedSubjectIds = [];
        $assignedClassroomIds = [];
        $resources = collect();

        $teacherId = $request->get('teacher_id');
        if ($teacherId) {
            $selectedTeacher = $teachers->firstWhere('id', (int) $teacherId);

            if ($selectedTeacher) {
                $assignedSubjectIds = $selectedTeacher->subjects()
                    ->wherePivot('school_id', $schoolId)
                    ->pluck('subjects.id')
                    ->all();

                $assignedClassroomIds = $selectedTeacher->teacherClassrooms()
                    ->wherePivot('school_id', $schoolId)
                    ->pluck('classrooms.id')
                    ->all();

                $resources = TeacherPedagogicalResource::query()
                    ->where('school_id', $schoolId)
                    ->where('teacher_id', (int) $selectedTeacher->id)
                    ->with(['subject:id,name', 'classroom:id,name'])
                    ->latest('id')
                    ->get();
            }
        }

        return view('admin.teachers.pedagogy', compact(
            'teachers',
            'subjects',
            'classrooms',
            'selectedTeacher',
            'assignedSubjectIds',
            'assignedClassroomIds',
            'resources'
        ));
    }

    public function update(Request $request, User $teacher)
    {
        abort_unless($teacher->role === 'teacher', 404);

        $schoolId = auth()->user()->school_id;
        abort_unless((int) $teacher->school_id === (int) $schoolId, 403);

        $data = $request->validate([
            'subjects' => ['nullable', 'array'],
            'subjects.*' => [
                'integer',
                Rule::exists('subjects', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'classrooms' => ['nullable', 'array'],
            'classrooms.*' => [
                'integer',
                Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
        ]);

        $assignedBy = auth()->id();

        $subjectSync = [];
        foreach (($data['subjects'] ?? []) as $subjectId) {
            $subjectSync[$subjectId] = [
                'school_id' => $schoolId,
                'assigned_by_user_id' => $assignedBy,
            ];
        }
        $teacher->subjects()->sync($subjectSync);

        $classroomSync = [];
        foreach (($data['classrooms'] ?? []) as $classroomId) {
            $classroomSync[$classroomId] = [
                'school_id' => $schoolId,
                'assigned_by_user_id' => $assignedBy,
            ];
        }
        $teacher->teacherClassrooms()->sync($classroomSync);

        return back()->with('success', 'Affectations mises a jour.');
    }

    public function storeResource(Request $request, User $teacher)
    {
        abort_unless($teacher->role === 'teacher', 404);

        $schoolId = auth()->user()->school_id;
        abort_unless((int) $teacher->school_id === (int) $schoolId, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'classroom_id' => ['nullable', Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'resource' => ['required', 'file', 'max:10240'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $storedPath = $request->file('resource')->store('pedagogy-resources', 'public');

        TeacherPedagogicalResource::create([
            'school_id' => $schoolId,
            'teacher_id' => (int) $teacher->id,
            'subject_id' => (int) ($data['subject_id'] ?? 0) ?: null,
            'classroom_id' => (int) ($data['classroom_id'] ?? 0) ?: null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $storedPath,
            'mime_type' => $request->file('resource')?->getMimeType(),
            'size_bytes' => (int) ($request->file('resource')?->getSize() ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by_user_id' => (int) auth()->id(),
        ]);

        return redirect()->route('admin.teachers.pedagogy', ['teacher_id' => $teacher->id])
            ->with('success', 'Ressource pedagogique ajoutee.');
    }

    public function destroyResource(User $teacher, TeacherPedagogicalResource $resource)
    {
        abort_unless($teacher->role === 'teacher', 404);
        $schoolId = auth()->user()->school_id;
        abort_unless(
            (int) $teacher->school_id === (int) $schoolId
            && (int) $resource->school_id === (int) $schoolId
            && (int) $resource->teacher_id === (int) $teacher->id,
            404
        );

        if ($resource->file_path && Storage::disk('public')->exists($resource->file_path)) {
            Storage::disk('public')->delete($resource->file_path);
        }

        $resource->delete();

        return redirect()->route('admin.teachers.pedagogy', ['teacher_id' => $teacher->id])
            ->with('success', 'Ressource pedagogique supprimee.');
    }
}
