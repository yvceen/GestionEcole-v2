<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class ParentsController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $q = trim((string) $request->get('q', ''));
        $classroomId = $request->integer('classroom_id') ?: null;
        $childName = trim((string) $request->get('child_name', ''));

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $parents = User::where('school_id', $schoolId)
            ->where('role', 'parent')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($classroomId || $childName !== '', function ($qq) use ($schoolId, $classroomId, $childName) {
                $qq->whereHas('children', function ($studentQuery) use ($schoolId, $classroomId, $childName) {
                    $studentQuery->where('school_id', $schoolId)
                        ->when($classroomId, fn ($query) => $query->where('classroom_id', $classroomId))
                        ->when($childName !== '', fn ($query) => $query->where('full_name', 'like', "%{$childName}%"));
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $parentIds = $parents->pluck('id')->all();

        $childrenByParent = Student::where('school_id', $schoolId)
            ->whereIn('parent_user_id', $parentIds)
            ->with(['classroom.level'])
            ->orderBy('full_name')
            ->get()
            ->groupBy('parent_user_id');

        return view('director.parents.index', compact('parents', 'childrenByParent', 'q', 'classrooms', 'classroomId', 'childName'));
    }
}
