<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentAcademicYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentPlacementService
{
    public function placementsForStudents(array|Collection $studentIds, int $schoolId, ?int $academicYearId): Collection
    {
        $studentIds = collect($studentIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($studentIds->isEmpty() || !$this->supportsPlacements() || !$academicYearId) {
            return collect();
        }

        return StudentAcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('student_id', $studentIds->all())
            ->with('classroom:id,name')
            ->get()
            ->keyBy('student_id');
    }

    public function placementForStudent(Student $student, int $schoolId, ?int $academicYearId): ?StudentAcademicYear
    {
        if (!$this->supportsPlacements() || !$academicYearId) {
            return null;
        }

        return StudentAcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->with('classroom:id,name')
            ->first();
    }

    public function classroomIdForStudent(Student $student, int $schoolId, ?int $academicYearId): ?int
    {
        $placement = $this->placementForStudent($student, $schoolId, $academicYearId);

        return $placement?->classroom_id ?: $student->classroom_id;
    }

    public function classroomNameForStudent(Student $student, int $schoolId, ?int $academicYearId): string
    {
        $placement = $this->placementForStudent($student, $schoolId, $academicYearId);

        if ($placement?->classroom?->name) {
            return (string) $placement->classroom->name;
        }

        return (string) ($student->classroom?->name ?? '');
    }

    public function syncCurrentStudentClassroom(Student $student, int $schoolId, ?int $academicYearId): void
    {
        $classroomId = $this->classroomIdForStudent($student, $schoolId, $academicYearId);
        if ($classroomId && (int) $student->classroom_id !== $classroomId) {
            $student->forceFill(['classroom_id' => $classroomId])->save();
        }
    }

    public function supportsPlacements(): bool
    {
        return Schema::hasTable('student_academic_years');
    }
}
