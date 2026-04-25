<?php

namespace App\Services;

use App\Models\AcademicYear;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicYearService
{
    public function getCurrentYearForSchool(int $schoolId): ?AcademicYear
    {
        if ($schoolId <= 0 || !Schema::hasTable('academic_years')) {
            return null;
        }

        $current = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->latest('starts_at')
            ->first();

        if ($current) {
            return $current;
        }

        $today = now()->toDateString();

        $active = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today)
            ->orderByDesc('starts_at')
            ->first();

        if ($active) {
            AcademicYear::query()
                ->where('school_id', $schoolId)
                ->whereKeyNot($active->id)
                ->update(['is_current' => false]);

            if (!$active->is_current) {
                $active->forceFill([
                    'is_current' => true,
                    'status' => $active->status ?: AcademicYear::STATUS_ACTIVE,
                ])->save();
            }

            return $active->fresh();
        }

        return AcademicYear::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('starts_at')
            ->first();
    }

    public function requireCurrentYearForSchool(int $schoolId): AcademicYear
    {
        return $this->getCurrentYearForSchool($schoolId)
            ?? $this->createDefaultYearForSchool($schoolId);
    }

    public function createDefaultYearForSchool(int $schoolId, ?CarbonInterface $today = null): AcademicYear
    {
        $today ??= now();
        ['name' => $name, 'starts_at' => $startsAt, 'ends_at' => $endsAt] = $this->defaultYearPayload($today);

        return DB::transaction(function () use ($schoolId, $name, $startsAt, $endsAt): AcademicYear {
            $existing = AcademicYear::query()
                ->where('school_id', $schoolId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                AcademicYear::query()
                    ->where('school_id', $schoolId)
                    ->whereKeyNot($existing->id)
                    ->update(['is_current' => false]);

                $existing->forceFill([
                    'starts_at' => $existing->starts_at ?: $startsAt,
                    'ends_at' => $existing->ends_at ?: $endsAt,
                    'is_current' => true,
                    'status' => $existing->status ?: AcademicYear::STATUS_ACTIVE,
                ])->save();

                return $existing->fresh();
            }

            AcademicYear::query()
                ->where('school_id', $schoolId)
                ->update(['is_current' => false]);

            return AcademicYear::query()->create([
                'school_id' => $schoolId,
                'name' => $name,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'is_current' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);
        });
    }

    public function resolveYearForSchool(int $schoolId, ?int $academicYearId): AcademicYear
    {
        if ($academicYearId && Schema::hasTable('academic_years')) {
            $requested = AcademicYear::query()
                ->where('school_id', $schoolId)
                ->find($academicYearId);

            if ($requested) {
                return $requested;
            }
        }

        return $this->requireCurrentYearForSchool($schoolId);
    }

    public function switchCurrentYear(int $schoolId, int $academicYearId): AcademicYear
    {
        return DB::transaction(function () use ($schoolId, $academicYearId): AcademicYear {
            $year = AcademicYear::query()
                ->where('school_id', $schoolId)
                ->findOrFail($academicYearId);

            AcademicYear::query()
                ->where('school_id', $schoolId)
                ->update(['is_current' => false]);

            $year->forceFill([
                'is_current' => true,
                'status' => $year->status === AcademicYear::STATUS_ARCHIVED
                    ? AcademicYear::STATUS_ACTIVE
                    : ($year->status ?: AcademicYear::STATUS_ACTIVE),
            ])->save();

            return $year->fresh();
        });
    }

    public function applyYearScope(
        Builder $query,
        int $schoolId,
        ?int $requestedAcademicYearId = null,
        ?string $table = null,
        bool $includeUnassignedDuringTransition = true,
    ): Builder {
        $table ??= $query->getModel()->getTable();

        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'academic_year_id')) {
            return $query;
        }

        $year = $this->resolveYearForSchool($schoolId, $requestedAcademicYearId);
        $includeUnassigned = $includeUnassignedDuringTransition && !$requestedAcademicYearId;

        return $query->where(function (Builder $builder) use ($table, $year, $includeUnassigned): void {
            $builder->where("{$table}.academic_year_id", $year->id);

            if ($includeUnassigned) {
                $builder->orWhereNull("{$table}.academic_year_id");
            }
        });
    }

    public function defaultYearPayload(?CarbonInterface $today = null): array
    {
        $today ??= now();
        $today = Carbon::instance($today instanceof Carbon ? $today : Carbon::parse($today));
        $startYear = (int) $today->month >= 9 ? (int) $today->year : (int) $today->year - 1;
        $endYear = $startYear + 1;

        return [
            'name' => sprintf('%d/%d', $startYear, $endYear),
            'starts_at' => Carbon::create($startYear, 9, 1)->startOfDay(),
            'ends_at' => Carbon::create($endYear, 8, 31)->endOfDay(),
        ];
    }
}
