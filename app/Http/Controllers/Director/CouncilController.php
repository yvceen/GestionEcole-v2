<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\ClassCouncil;
use App\Models\Classroom;
use Illuminate\Http\Request;

class CouncilController extends Controller
{
    public function index()
    {
        $schoolId = auth()->user()->school_id;

        $councils = ClassCouncil::whereHas('classroom', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->with('classroom')
            ->latest('date')
            ->paginate(20);

        return view('director.councils.index', compact('councils'));
    }

    public function create()
    {
        $schoolId = auth()->user()->school_id;

        $classrooms = Classroom::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        return view('director.councils.create', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        $data = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'decisions' => ['nullable', 'string'],
        ]);

        // 🔐 تأكد أن classroom تابع لنفس المدرسة
        $classroom = Classroom::where('id', $data['classroom_id'])
            ->where('school_id', $schoolId)
            ->firstOrFail();

        ClassCouncil::create([
            'classroom_id' => $classroom->id,
            'date' => $data['date'],
            'title' => $data['title'],
            'decisions' => $data['decisions'] ?? null,
            'created_by_user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('director.councils.index')
            ->with('success', 'Conseil enregistré.');
    }
}