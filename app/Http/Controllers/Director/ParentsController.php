<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class ParentsController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $q = trim((string)$request->get('q',''));

        $parents = User::where('school_id',$schoolId)
            ->where('role','parent')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%")
                      ->orWhere('phone','like',"%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $parentIds = $parents->pluck('id')->all();

        $childrenByParent = Student::where('school_id',$schoolId)
            ->whereIn('parent_user_id',$parentIds)
            ->with(['classroom.level'])
            ->orderBy('full_name')
            ->get()
            ->groupBy('parent_user_id');

        return view('director.parents.index', compact('parents','childrenByParent','q'));
    }
}
