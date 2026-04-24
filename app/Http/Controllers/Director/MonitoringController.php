<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Homework;
use App\Models\Level;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $levelId = $request->integer('level_id') ?: null;
        $classroomId = $request->integer('classroom_id') ?: null;
        $teacherId = $request->integer('teacher_id') ?: null;

        $type = $request->get('type', 'courses'); // courses|homeworks
        $q = trim((string)$request->get('q',''));

        $dateFrom = $request->get('from') ? Carbon::parse($request->get('from'))->startOfDay() : null;
        $dateTo   = $request->get('to')   ? Carbon::parse($request->get('to'))->endOfDay()   : null;

        $levels = Level::where('school_id',$schoolId)->orderBy('name')->get(['id','name']);
        $classrooms = Classroom::where('school_id',$schoolId)
            ->when($levelId, fn($qq)=>$qq->where('level_id',$levelId))
            ->orderBy('name')->get(['id','name','level_id']);

        $teachers = User::where('school_id',$schoolId)->where('role','teacher')->orderBy('name')->get(['id','name','is_active']);

        if ($type === 'homeworks') {
            $items = Homework::query()
                ->where('school_id',$schoolId)
                ->with(['classroom.level','teacher'])
                ->when($classroomId, fn($qq)=>$qq->where('classroom_id',$classroomId))
                ->when($teacherId, fn($qq)=>$qq->where('teacher_id',$teacherId))
                ->when($q !== '', fn($qq)=>$qq->where(function($w) use ($q){
                    $w->where('title','like',"%$q%")->orWhere('description','like',"%$q%");
                }))
                ->when($dateFrom && $dateTo, fn($qq)=>$qq->whereBetween('created_at',[$dateFrom,$dateTo]))
                ->latest()
                ->paginate(15)
                ->withQueryString();
        } else {
            $items = Course::query()
                ->where('school_id',$schoolId)
                ->with(['classroom.level','teacher','attachments'])
                ->when($classroomId, fn($qq)=>$qq->where('classroom_id',$classroomId))
                ->when($teacherId, fn($qq)=>$qq->where('teacher_id',$teacherId))
                ->when($q !== '', fn($qq)=>$qq->where(function($w) use ($q){
                    $w->where('title','like',"%$q%")->orWhere('description','like',"%$q%");
                }))
                ->when($dateFrom && $dateTo, fn($qq)=>$qq->whereBetween('created_at',[$dateFrom,$dateTo]))
                ->latest()
                ->paginate(15)
                ->withQueryString();
        }

        return view('director.monitoring', compact(
            'levels','classrooms','teachers',
            'levelId','classroomId','teacherId','type','q','dateFrom','dateTo',
            'items'
        ));
    }
}
