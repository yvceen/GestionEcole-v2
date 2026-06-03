<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\EducationCycle;
use App\Models\Grade;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StructureController extends Controller
{
    private const DEFAULT_CYCLES = [
        ['code' => 'MAT', 'name' => 'Maternelle', 'color' => 'rose', 'sort_order' => 10],
        ['code' => 'PRI', 'name' => 'Primaire', 'color' => 'sky', 'sort_order' => 20],
        ['code' => 'COL', 'name' => 'Collège', 'color' => 'amber', 'sort_order' => 30],
        ['code' => 'LYC', 'name' => 'Lycée', 'color' => 'violet', 'sort_order' => 40],
    ];

    private const DEFAULT_LEVELS = [
        'MAT' => [['PS', 'Petite section'], ['MS', 'Moyenne section'], ['GS', 'Grande section']],
        'PRI' => [['1AP', '1ère année primaire'], ['2AP', '2ème année primaire'], ['3AP', '3ème année primaire'], ['4AP', '4ème année primaire'], ['5AP', '5ème année primaire'], ['6AP', '6ème année primaire']],
        'COL' => [['1AC', '1ère année collège'], ['2AC', '2ème année collège'], ['3AC', '3ème année collège']],
        'LYC' => [['TC', 'Tronc commun'], ['1BAC', '1ère année baccalauréat'], ['2BAC', '2ème année baccalauréat']],
    ];

    public function index()
    {
        $this->ensureDefaultCycles();

        $cycles = EducationCycle::query()
            ->with(['levels.classrooms' => fn ($query) => $query
                ->withCount(['students' => fn ($students) => $students->active()])
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->withCount('levels')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $unassignedLevels = Level::query()
            ->whereNull('education_cycle_id')
            ->with(['classrooms' => fn ($query) => $query
                ->withCount(['students' => fn ($students) => $students->active()])
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $levels = $cycles->flatMap->levels->concat($unassignedLevels);

        return view('admin.structure.index', [
            'cycles' => $cycles,
            'levels' => $levels,
            'unassignedLevels' => $unassignedLevels,
            'routePrefix' => request()->routeIs('school-life.*') ? 'school-life.structure' : 'admin.structure',
            'layoutComponent' => request()->routeIs('school-life.*') ? 'school-life-layout' : 'admin-layout',
        ]);
    }

    public function installPresets()
    {
        $this->ensureDefaultCycles();
        $created = 0;

        DB::transaction(function () use (&$created): void {
            foreach (self::DEFAULT_LEVELS as $cycleCode => $levels) {
                $cycle = EducationCycle::query()->where('code', $cycleCode)->firstOrFail();
                foreach ($levels as $position => [$code, $name]) {
                    $level = Level::query()->firstOrCreate(
                        ['code' => $code],
                        [
                            'education_cycle_id' => $cycle->id,
                            'name' => $name,
                            'sort_order' => ($position + 1) * 10,
                            'is_active' => true,
                        ],
                    );
                    if ($level->wasRecentlyCreated) {
                        $created++;
                    } elseif (!$level->education_cycle_id) {
                        $level->update(['education_cycle_id' => $cycle->id]);
                    }
                }
            }
        });

        return back()->with('success', $created > 0
            ? "{$created} niveau(x) standard(s) ajouté(s)."
            : 'La structure standard est déjà installée.');
    }

    public function storeCycle(Request $request)
    {
        $data = $request->validate($this->cycleRules());

        EducationCycle::query()->create([
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'color' => $data['color'],
            'sort_order' => (EducationCycle::query()->max('sort_order') ?? 0) + 10,
            'is_active' => true,
        ]);

        return back()->with('success', 'Cycle ajouté.');
    }

    public function updateCycle(Request $request, EducationCycle $cycle)
    {
        $data = $request->validate($this->cycleRules($cycle));
        $cycle->update([
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'color' => $data['color'],
        ]);

        return back()->with('success', 'Cycle modifié.');
    }

    public function destroyCycle(EducationCycle $cycle)
    {
        if ($cycle->levels()->exists()) {
            return back()->withErrors(['delete_cycle' => 'Impossible : ce cycle contient encore des niveaux.']);
        }

        $cycle->delete();

        return back()->with('success', 'Cycle supprimé.');
    }

    public function storeLevel(Request $request)
    {
        $data = $request->validate($this->levelRules());

        Level::query()->create([
            'education_cycle_id' => $data['education_cycle_id'],
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'sort_order' => (Level::query()->where('education_cycle_id', $data['education_cycle_id'])->max('sort_order') ?? 0) + 10,
            'is_active' => true,
        ]);

        return back()->with('success', 'Niveau ajouté.');
    }

    public function updateLevel(Request $request, Level $level)
    {
        $data = $request->validate($this->levelRules($level));
        $level->update([
            'education_cycle_id' => $data['education_cycle_id'],
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
        ]);

        return back()->with('success', 'Niveau modifié.');
    }

    public function destroyLevel(Level $level)
    {
        if ($level->classrooms()->exists()) {
            return back()->withErrors(['delete_level' => 'Impossible : ce niveau contient des classes.']);
        }

        $level->delete();

        return back()->with('success', 'Niveau supprimé.');
    }

    public function storeClassroom(Request $request)
    {
        $data = $request->validate([
            'level_id' => ['required', Rule::exists('levels', 'id')->where(fn ($query) => $query->where('school_id', app('current_school_id')))],
            'name' => ['required', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:20'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $level = Level::query()->findOrFail($data['level_id']);
        $this->createClassroom($level, trim($data['name']), (string) ($data['section'] ?? ''), $data['capacity'] ?? null);

        return back()->with('success', 'Classe ajoutée.');
    }

    public function storeClassroomsBulk(Request $request)
    {
        $data = $request->validate([
            'level_id' => ['required', Rule::exists('levels', 'id')->where(fn ($query) => $query->where('school_id', app('current_school_id')))],
            'sections' => ['required', 'string', 'max:150'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $level = Level::query()->findOrFail($data['level_id']);
        $sections = collect(preg_split('/[\s,;]+/', strtoupper($data['sections'])))
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->take(20);

        if ($sections->isEmpty()) {
            return back()->withErrors(['sections' => 'Ajoutez au moins une section, par exemple A, B, C.'])->withInput();
        }

        $created = 0;
        foreach ($sections as $section) {
            $name = $level->code . '-' . $section;
            if (!Classroom::query()->where('level_id', $level->id)->where('section', $section)->exists()) {
                $this->createClassroom($level, $name, $section, $data['capacity'] ?? null);
                $created++;
            }
        }

        return back()->with('success', "{$created} classe(s) créée(s) pour {$level->code}.");
    }

    public function updateClassroom(Request $request, Classroom $classroom)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:20'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $section = $this->normalizeSection((string) ($data['section'] ?? ''), $data['name']);
        $duplicate = Classroom::query()->where('level_id', $classroom->level_id)
            ->where('section', $section)
            ->whereKeyNot($classroom->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['section' => "La section {$section} existe déjà dans ce niveau."]);
        }

        $classroom->update([
            'name' => trim($data['name']),
            'section' => $section,
            'capacity' => $data['capacity'] ?? null,
        ]);

        return back()->with('success', 'Classe modifiée.');
    }

    public function destroyClassroom(Classroom $classroom)
    {
        if ($classroom->students()->exists()) {
            return back()->withErrors(['delete_classroom' => 'Impossible : cette classe contient encore des élèves.']);
        }
        if (Assessment::query()->where('classroom_id', $classroom->id)->exists() || Grade::query()->where('classroom_id', $classroom->id)->exists()) {
            return back()->withErrors(['delete_classroom' => 'Impossible : cette classe contient encore des données pédagogiques.']);
        }

        $classroom->delete();

        return back()->with('success', 'Classe supprimée.');
    }

    public function showClassroom(Classroom $classroom)
    {
        $classroom->load('level.cycle');
        $students = $classroom->students()->with('parentUser')->active()->orderBy('full_name')->get();

        return view('admin.structure.classroom', [
            'classroom' => $classroom,
            'students' => $students,
            'routePrefix' => request()->routeIs('school-life.*') ? 'school-life.structure' : 'admin.structure',
            'layoutComponent' => request()->routeIs('school-life.*') ? 'school-life-layout' : 'admin-layout',
        ]);
    }

    private function ensureDefaultCycles(): void
    {
        foreach (self::DEFAULT_CYCLES as $preset) {
            EducationCycle::query()->firstOrCreate(
                ['code' => $preset['code']],
                [...$preset, 'is_active' => true],
            );
        }
    }

    private function cycleRules(?EducationCycle $cycle = null): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('education_cycles', 'code')
                ->where(fn ($query) => $query->where('school_id', app('current_school_id')))
                ->ignore($cycle?->id)],
            'name' => ['required', 'string', 'max:80'],
            'color' => ['required', Rule::in(['rose', 'sky', 'amber', 'violet', 'emerald', 'slate'])],
        ];
    }

    private function levelRules(?Level $level = null): array
    {
        return [
            'education_cycle_id' => ['required', Rule::exists('education_cycles', 'id')->where(fn ($query) => $query->where('school_id', app('current_school_id')))],
            'code' => ['required', 'string', 'max:10', Rule::unique('levels', 'code')
                ->where(fn ($query) => $query->where('school_id', app('current_school_id')))
                ->ignore($level?->id)],
            'name' => ['required', 'string', 'max:80'],
        ];
    }

    private function createClassroom(Level $level, string $name, string $section, ?int $capacity): Classroom
    {
        $section = $this->normalizeSection($section, $name);
        if (Classroom::query()->where('level_id', $level->id)->where('section', $section)->exists()) {
            throw ValidationException::withMessages([
                'section' => "La section {$section} existe déjà dans ce niveau.",
            ]);
        }

        return Classroom::query()->create([
            'level_id' => $level->id,
            'name' => $name,
            'section' => $section,
            'capacity' => $capacity,
            'sort_order' => (Classroom::query()->where('level_id', $level->id)->max('sort_order') ?? 0) + 10,
            'is_active' => true,
        ]);
    }

    private function normalizeSection(string $section, string $name): string
    {
        $value = trim($section) !== '' ? $section : $name;

        return strtoupper(trim(preg_replace('/\s+/', '-', $value), '-'));
    }
}
