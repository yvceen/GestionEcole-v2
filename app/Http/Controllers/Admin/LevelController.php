<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    public function index()
    {
        $levels = Level::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.levels.index', compact('levels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['nullable','string','max:30'],
            'name' => ['required','string','max:120'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? true);
        $data['sort_order'] = (int)($data['sort_order'] ?? 0);

        Level::create($data);

        return redirect()->route('admin.levels.index')->with('success', 'Niveau ajouté.');
    }

    public function edit(Level $level)
    {
        return view('admin.levels.edit', compact('level'));
    }

    public function update(Request $request, Level $level)
    {
        $data = $request->validate([
            'code' => ['nullable','string','max:30'],
            'name' => ['required','string','max:120'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? false);
        $data['sort_order'] = (int)($data['sort_order'] ?? 0);

        $level->update($data);

        return redirect()->route('admin.levels.index')->with('success', 'Niveau modifié.');
    }

    public function destroy(Level $level)
    {
        // إذا كاين classes تابعين له، خليه ما يتحيدش بسهولة
        if ($level->classrooms()->count() > 0) {
            return back()->withErrors(['delete' => 'هاد المستوى فيه Classes، حيد Classes قبل.']);
        }

        $level->delete();
        return redirect()->route('admin.levels.index')->with('success', 'Niveau supprimé.');
    }
}
