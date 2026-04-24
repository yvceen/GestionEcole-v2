<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\MonthlyPedagogicReport;
use App\Models\Classroom;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
        $schoolId = auth()->user()->school_id;

        $reports = MonthlyPedagogicReport::where('school_id', $schoolId)
            ->with('classroom')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(20);

        return view('director.reports.index', compact('reports'));
    }

    public function create()
    {
        $schoolId = auth()->user()->school_id;
        $classrooms = Classroom::where('school_id', $schoolId)->orderBy('name')->get();

        return view('director.reports.create', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        $data = $request->validate([
            'classroom_id' => ['nullable','exists:classrooms,id'],
            'year' => ['required','integer','min:2020','max:2100'],
            'month' => ['required','integer','min:1','max:12'],
            'summary' => ['nullable','string'],
            'recommendations' => ['nullable','string'],
        ]);

        MonthlyPedagogicReport::create([
            'school_id' => $schoolId,
            'classroom_id' => $data['classroom_id'] ?? null,
            'year' => $data['year'],
            'month' => $data['month'],
            'summary' => $data['summary'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
            'created_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('director.reports.index')->with('success','Rapport enregistré.');
    }
}
