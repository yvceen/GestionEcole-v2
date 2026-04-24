<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Homework;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TeachersController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $q = trim((string) $request->get('q', ''));

        $teachers = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'teacher')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->with(['teacherClassrooms.level'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // ✅ FIX: stats safe (ila teacher_id ma kaynch f courses/homeworks)
        $coursesHasTeacherId   = Schema::hasColumn('courses', 'teacher_id');
        $homeworksHasTeacherId = Schema::hasColumn('homeworks', 'teacher_id');

        $teachers->setCollection(
            $teachers->getCollection()->map(function ($t) use ($schoolId, $coursesHasTeacherId, $homeworksHasTeacherId) {

            // defaults (باش ما يطيحش view)
            $t->courses_count = 0;
            $t->homeworks_count = 0;
            $t->last_activity = null;

            $lastCourse = null;
            $lastHomework = null;

            // Courses stats
            if ($coursesHasTeacherId) {
                $t->courses_count = Course::query()
                    ->where('school_id', $schoolId)
                    ->where('teacher_id', $t->id)
                    ->count();

                $lastCourse = Course::query()
                    ->where('school_id', $schoolId)
                    ->where('teacher_id', $t->id)
                    ->latest('created_at')
                    ->value('created_at');
            }

            // Homeworks stats
            if ($homeworksHasTeacherId) {
                $t->homeworks_count = Homework::query()
                    ->where('school_id', $schoolId)
                    ->where('teacher_id', $t->id)
                    ->count();

                $lastHomework = Homework::query()
                    ->where('school_id', $schoolId)
                    ->where('teacher_id', $t->id)
                    ->latest('created_at')
                    ->value('created_at');
            }

            $t->last_activity = collect([$lastCourse, $lastHomework])
                ->filter()
                ->sortDesc()
                ->first();

            return $t;
        })
        );

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->with('level')
            ->orderBy('name')
            ->get(['id', 'name', 'level_id']);

        return view('director.teachers.index', compact('teachers', 'classrooms', 'q'));
    }

    public function toggleActive(Request $request, User $teacher)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        abort_unless((int) $teacher->school_id === (int) $schoolId && $teacher->role === 'teacher', 404);

        $teacher->is_active = !((bool) $teacher->is_active);
        $teacher->save();

        return back()->with('success', 'Statut mis à jour ✅');
    }

    public function assignClassrooms(Request $request, User $teacher)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        abort_unless((int) $teacher->school_id === (int) $schoolId && $teacher->role === 'teacher', 404);

        $classroomIds = collect($request->input('classrooms', []))
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        // verify classrooms belong to this school
        $validIds = Classroom::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $classroomIds)
            ->pluck('id')
            ->all();

        // sync pivot with pivot data
        $syncData = [];
        foreach ($validIds as $cid) {
            $syncData[$cid] = [
                'school_id' => $schoolId,
                'assigned_by_user_id' => auth()->id(),
            ];
        }

        $teacher->teacherClassrooms()->sync($syncData);

        return back()->with('success', 'Affectations mises à jour ✅');
    }
}
