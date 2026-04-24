<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Homework;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportsController extends Controller
{
    public function monthlyCsv(Request $request): StreamedResponse
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $month = $request->get('month'); // optional: YYYY-MM
        $start = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $filename = "report_{$start->format('Y_m')}.csv";

        return response()->streamDownload(function () use ($schoolId, $start, $end) {
            $out = fopen('php://output', 'w');

            // header
            fputcsv($out, [
                'Period',
                'Students',
                'Teachers',
                'Parents',
                'Classrooms',
                'Courses',
                'Homeworks',
            ]);

            $students = Student::where('school_id',$schoolId)->count();
            $teachers = User::where('school_id',$schoolId)->where('role','teacher')->count();
            $parents  = User::where('school_id',$schoolId)->where('role','parent')->count();
            $classrooms = Classroom::where('school_id',$schoolId)->count();

            $courses = Course::where('school_id',$schoolId)->whereBetween('created_at',[$start,$end])->count();
            $homeworks = Homework::where('school_id',$schoolId)->whereBetween('created_at',[$start,$end])->count();

            fputcsv($out, [
                $start->format('Y-m'),
                $students,
                $teachers,
                $parents,
                $classrooms,
                $courses,
                $homeworks,
            ]);

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
