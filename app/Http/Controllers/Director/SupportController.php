<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()?->school_id;
        if (!$schoolId) abort(403, 'School context missing.');

        // Filters
        $classroomId = $request->get('classroom_id');
        $period      = $request->get('period', 'month'); // month|trimester|year

        // Period window (based on grades.created_at)
        $from = match ($period) {
            'trimester' => now()->subMonths(3),
            'year'      => now()->subYear(),
            default     => now()->startOfMonth(),
        };

        $threshold = 10; // moyenne < 10 => difficulté

        // Dropdown classrooms
        $classrooms = Classroom::where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id','name']);

        /*
        |--------------------------------------------------------------------------
        | Base: students in this school (+ optional classroom filter)
        |--------------------------------------------------------------------------
        */
        $studentsBase = Student::query()
            ->where('students.school_id', $schoolId)
            ->when($classroomId, fn($q) => $q->where('students.classroom_id', $classroomId));

        /*
        |--------------------------------------------------------------------------
        | Build ranking: AVG per student (grades in chosen period)
        |--------------------------------------------------------------------------
        */
        $rows = (clone $studentsBase)
            ->leftJoin('grades', function ($join) use ($schoolId, $from) {
                $join->on('grades.student_id', '=', 'students.id')
                    ->where('grades.school_id', '=', $schoolId)
                    ->where('grades.created_at', '>=', $from);
            })
            ->leftJoin('classrooms', function ($join) use ($schoolId) {
                $join->on('classrooms.id', '=', 'students.classroom_id')
                    ->where('classrooms.school_id', '=', $schoolId);
            })
            ->selectRaw('
                students.id as student_id,
                students.full_name as student_name,
                students.classroom_id as classroom_id,
                classrooms.name as classroom_name,
                COUNT(grades.id) as notes_count,
                ROUND(AVG(grades.score), 2) as avg_score
            ')
            ->groupBy('students.id', 'students.full_name', 'students.classroom_id', 'classrooms.name')
            // students with no grades go last
            ->orderByRaw('avg_score IS NULL')
            ->orderBy('avg_score', 'asc')
            ->limit(50) // take more to compute weak subjects later
            ->get();

        // Keep only "en difficulté" (avg < threshold) + take top 20
        $rows = $rows
            ->filter(fn($r) => $r->avg_score !== null && (float)$r->avg_score < $threshold)
            ->take(20)
            ->values();

        // Student ids for details
        $studentIds = $rows->pluck('student_id')->all();

        /*
        |--------------------------------------------------------------------------
        | Weak subjects per student (avg per subject < threshold)
        |--------------------------------------------------------------------------
        */
        $weakSubjectsMap = [];
        if (!empty($studentIds)) {
            $weak = DB::table('grades')
                ->join('subjects', 'subjects.id', '=', 'grades.subject_id')
                ->where('grades.school_id', $schoolId)
                ->whereIn('grades.student_id', $studentIds)
                ->where('grades.created_at', '>=', $from)
                ->groupBy('grades.student_id', 'grades.subject_id', 'subjects.name')
                ->havingRaw('AVG(grades.score) < ?', [$threshold])
                ->selectRaw('
                    grades.student_id,
                    subjects.name as subject_name,
                    ROUND(AVG(grades.score), 2) as avg_subject
                ')
                ->orderBy('avg_subject', 'asc')
                ->get();

            foreach ($weak as $w) {
                $weakSubjectsMap[$w->student_id] ??= [];
                // مثال: "Math (8.50)"
                $weakSubjectsMap[$w->student_id][] = $w->subject_name . ' (' . number_format((float)$w->avg_subject, 2) . ')';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Build items for blade
        |--------------------------------------------------------------------------
        */
        $items = $rows->map(function ($r) use ($weakSubjectsMap, $threshold) {
            $avg = (float)$r->avg_score;

            // simple action text
            $action = $avg < 7
                ? 'Soutien intensif + contact parent'
                : 'Soutien ciblé + suivi hebdomadaire';

            return [
                'student_id'    => (int)$r->student_id,
                'student_name'  => $r->student_name ?? ('Élève #' . $r->student_id),
                'classroom'     => $r->classroom_name ?? '—',
                'avg'           => number_format($avg, 2) . ' /20',
                'notes_count'   => (int)$r->notes_count,
                'weak_subjects' => $weakSubjectsMap[$r->student_id] ?? [],
                'action'        => $action,
                'is_critical'   => $avg < 7,
                'threshold'     => $threshold,
            ];
        })->all();

        return view('director.support.index', compact(
            'items',
            'classrooms',
            'classroomId',
            'period',
            'threshold',
            'from'
        ));
    }
}