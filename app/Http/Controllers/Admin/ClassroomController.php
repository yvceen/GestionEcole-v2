<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Level;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q',''));

        $classrooms = Classroom::with('level')
            ->withCount(['students' => fn ($students) => $students->active()])
            ->when($q !== '', function($qq) use ($q) {
                $qq->where('name','like',"%{$q}%")
                   ->orWhere('section','like',"%{$q}%")
                   ->orWhereHas('level', fn($l) => $l->where('name','like',"%{$q}%"));
            })
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.classrooms.index', compact('classrooms','q'));
    }

    public function create()
    {
        showsLevelsWarningIfEmpty:
        $levels = Level::orderBy('sort_order')->orderBy('name')->get(['id','name','code','is_active']);
        return view('admin.classrooms.create', compact('levels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'level_id' => ['nullable','exists:levels,id'],
            'level_name' => ['nullable','string','max:120'], // allow create level from here
            'name' => ['required','string','max:120'],
            'section' => ['nullable','string','max:60'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ]);

        // ✅ If no level selected but level_name provided → create it
        if (empty($data['level_id']) && !empty($data['level_name'])) {
            $lvl = Level::firstOrCreate(
                ['name' => $data['level_name']],
                ['code' => null, 'sort_order' => 0, 'is_active' => true]
            );
            $data['level_id'] = $lvl->id;
        }

        // level still nullable if you want, but recommended
        $data['is_active'] = (bool)($data['is_active'] ?? true);
        $data['sort_order'] = (int)($data['sort_order'] ?? 0);

        unset($data['level_name']);

        Classroom::create($data);

        return redirect()->route('admin.classrooms.index')->with('success', 'Classe ajoutée.');
    }

    public function show(Classroom $classroom)
    {
        $classroom->load('level');

        $students = $classroom->students()
            ->with(['parentUser'])
            ->active()
            ->orderBy('full_name')
            ->get();

        return view('admin.classrooms.show', compact('classroom','students'));
    }

    public function edit(Classroom $classroom)
    {
        $levels = Level::orderBy('sort_order')->orderBy('name')->get(['id','name','code','is_active']);
        return view('admin.classrooms.edit', compact('classroom','levels'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        $data = $request->validate([
            'level_id' => ['nullable','exists:levels,id'],
            'name' => ['required','string','max:120'],
            'section' => ['nullable','string','max:60'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? false);
        $data['sort_order'] = (int)($data['sort_order'] ?? 0);

        $classroom->update($data);

        return redirect()->route('admin.classrooms.show', $classroom)->with('success', 'Classe modifiée.');
    }

    public function destroy(Classroom $classroom)
    {
        // إذا فيها تلاميذ، ما نحيدوهاش باش ما يوقعش مشكل
        if ($classroom->students()->count() > 0) {
            return back()->withErrors(['delete' => 'هاد classe فيها تلاميذ. حوّلهم قبل ولا خليه is_active=0.']);
        }

        $classroom->delete();

        return redirect()->route('admin.classrooms.index')->with('success', 'Classe supprimée.');
    }
}
