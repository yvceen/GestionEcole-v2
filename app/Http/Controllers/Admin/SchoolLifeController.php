<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolLife;
use Illuminate\Http\Request;

class SchoolLifeController extends Controller
{
    public function index()
    {
        $items = SchoolLife::orderByDesc('date')->paginate(10);
        return view('admin.school-life.index', compact('items'));
    }

    public function create()
    {
        return view('admin.school-life.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date'],
        ]);

        SchoolLife::create($data);

        return redirect()->route('admin.school-life.index')->with('success', 'Vie scolaire creee.');
    }

    public function edit(SchoolLife $schoolLife)
    {
        return view('admin.school-life.edit', compact('schoolLife'));
    }

    public function update(Request $request, SchoolLife $schoolLife)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date'],
        ]);

        $schoolLife->update($data);

        return redirect()->route('admin.school-life.index')->with('success', 'Vie scolaire mise a jour.');
    }

    public function destroy(SchoolLife $schoolLife)
    {
        $schoolLife->delete();
        return redirect()->route('admin.school-life.index')->with('success', 'Vie scolaire supprimee.');
    }
}
