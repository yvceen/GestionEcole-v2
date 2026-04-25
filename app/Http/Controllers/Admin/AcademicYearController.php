<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\AcademicYearService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $academicYears,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $years = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('is_current')
            ->orderByDesc('starts_at')
            ->get();

        return view('admin.academic-years.index', [
            'years' => $years,
            'currentAcademicYear' => $this->academicYears->requireCurrentYearForSchool($schoolId),
        ]);
    }

    public function create()
    {
        $schoolId = $this->schoolId();
        $defaults = $this->academicYears->defaultYearPayload();

        return view('admin.academic-years.create', [
            'currentAcademicYear' => $this->academicYears->requireCurrentYearForSchool($schoolId),
            'defaults' => $defaults,
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $this->schoolId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('academic_years', 'name')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in(AcademicYear::statuses())],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $year = AcademicYear::query()->create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'status' => $data['status'],
            'is_current' => false,
        ]);

        if ((bool) ($data['is_current'] ?? false)) {
            $this->academicYears->switchCurrentYear($schoolId, (int) $year->id);
        }

        return redirect()
            ->route('admin.academic-years.index')
            ->with('success', 'Annee scolaire enregistree.');
    }

    public function activate(AcademicYear $academicYear)
    {
        $academicYear = $this->resolveAcademicYear($academicYear);
        $this->academicYears->switchCurrentYear($this->schoolId(), (int) $academicYear->id);

        return back()->with('success', 'Annee scolaire active mise a jour.');
    }

    public function archive(AcademicYear $academicYear)
    {
        $academicYear = $this->resolveAcademicYear($academicYear);

        if ($academicYear->is_current) {
            return back()->withErrors([
                'academic_year' => 'Activez une autre annee scolaire avant d archiver l annee courante.',
            ]);
        }

        $academicYear->update([
            'status' => AcademicYear::STATUS_ARCHIVED,
            'is_current' => false,
        ]);

        return back()->with('success', 'Annee scolaire archivee.');
    }

    private function resolveAcademicYear(AcademicYear $academicYear): AcademicYear
    {
        abort_unless((int) $academicYear->school_id === $this->schoolId(), 404);

        return $academicYear;
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
