<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StructureController extends Controller
{
    public function index()
    {
        $levels = Level::with([
            'classrooms' => function ($query) {
                $query->withCount(['students' => fn ($students) => $students->active()])
                    ->orderBy('sort_order')
                    ->orderBy('name');
            },
        ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.structure.index', compact('levels'));
    }

    public function storeLevel(Request $request)
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('levels', 'code')->where(fn ($query) => $query->where('school_id', app('current_school_id'))),
            ],
            'name' => ['required', 'string', 'max:50'],
        ]);

        Level::create([
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'sort_order' => (Level::max('sort_order') ?? 0) + 1,
            'is_active' => 1,
        ]);

        return back()->with('success', 'Niveau ajouté.');
    }

    public function updateLevel(Request $request, Level $level)
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('levels', 'code')
                    ->where(fn ($query) => $query->where('school_id', app('current_school_id')))
                    ->ignore($level->id),
            ],
            'name' => ['required', 'string', 'max:50'],
        ]);

        $level->update([
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
        ]);

        return back()->with('success', 'Niveau modifié.');
    }

    public function destroyLevel(Level $level)
    {
        if ($level->classrooms()->count() > 0) {
            return back()->withErrors([
                'delete_level' => 'Impossible : ce niveau contient des classes. Supprimez d\'abord les classes.',
            ]);
        }

        $level->delete();

        return back()->with('success', 'Niveau supprimé.');
    }

    public function storeClassroom(Request $request)
    {
        $data = $request->validate([
            'level_id' => ['required', 'exists:levels,id'],
            'name' => ['required', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:20'],
        ]);

        $level = Level::findOrFail($data['level_id']);
        $name = trim($data['name']);

        $section = trim((string) ($data['section'] ?? ''));
        $section = $section === ''
            ? strtoupper(str_replace(' ', '-', $name))
            : strtoupper($section);

        $nextSort = (Classroom::where('level_id', $level->id)->max('sort_order') ?? 0) + 1;

        $exists = Classroom::where('level_id', $level->id)
            ->where('section', $section)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'section' => "Cette classe existe déjà dans ce niveau (section : {$section}).",
            ])->withInput();
        }

        Classroom::create([
            'level_id' => $level->id,
            'name' => $name,
            'section' => $section,
            'sort_order' => $nextSort,
            'is_active' => 1,
        ]);

        return back()->with('success', 'Classe ajoutée.');
    }

    public function updateClassroom(Request $request, Classroom $classroom)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:20'],
        ]);

        $name = trim($data['name']);

        $section = trim((string) ($data['section'] ?? ''));
        $section = $section === ''
            ? strtoupper(str_replace(' ', '-', $name))
            : strtoupper($section);

        $exists = Classroom::where('level_id', $classroom->level_id)
            ->where('section', $section)
            ->where('id', '!=', $classroom->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'section' => "Cette section existe déjà dans ce niveau : {$section}",
            ]);
        }

        $classroom->update([
            'name' => $name,
            'section' => $section,
        ]);

        return back()->with('success', 'Classe modifiée.');
    }

    public function destroyClassroom(Classroom $classroom)
    {
        if ($classroom->students()->exists()) {
            return back()->withErrors([
                'delete_classroom' => 'Impossible : cette classe contient encore des élèves.',
            ]);
        }

        if (Assessment::where('classroom_id', $classroom->id)->exists()) {
            return back()->withErrors([
                'delete_classroom' => 'Impossible : cette classe est encore utilisée par des évaluations.',
            ]);
        }

        if (Grade::where('classroom_id', $classroom->id)->exists()) {
            return back()->withErrors([
                'delete_classroom' => 'Impossible : cette classe est encore utilisée par des notes.',
            ]);
        }

        $classroom->delete();

        return back()->with('success', 'Classe supprimée.');
    }

    public function showClassroom(Classroom $classroom)
    {
        $classroom->load('level');

        $students = \App\Models\Student::with('parentUser')
            ->where('classroom_id', $classroom->id)
            ->active()
            ->orderBy('full_name')
            ->get();

        return view('admin.structure.classroom', compact('classroom', 'students'));
    }
}
