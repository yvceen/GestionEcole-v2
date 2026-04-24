<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\School;
use App\Models\Student;
use App\Models\TimetableSetting;
use Carbon\Carbon;

class AttendanceAutoAbsentService
{
    public const CUT_OFF_TIME = '09:00:00';

    public function markDueAbsences(?int $schoolId = null, ?Carbon $now = null, bool $force = false): int
    {
        $now ??= now();

        $schools = School::query()
            ->when($schoolId, fn ($query) => $query->whereKey($schoolId), fn ($query) => $query->where('is_active', true))
            ->get(['id']);

        $created = 0;

        foreach ($schools as $school) {
            $created += $this->markDueAbsencesForSchool((int) $school->id, $now);
        }

        return $created;
    }

    public function markDueAbsencesForSchool(int $schoolId, ?Carbon $now = null, bool $force = false): int
    {
        $now ??= now();

        if ($schoolId <= 0 || !$this->shouldRunForSchool($schoolId, $now, $force)) {
            return 0;
        }

        $date = $now->toDateString();
        $created = 0;

        $existingStudentIds = Attendance::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', $date)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        Student::query()
            ->where('school_id', $schoolId)
            ->active()
            ->when(!empty($existingStudentIds), fn ($query) => $query->whereNotIn('id', $existingStudentIds))
            ->orderBy('id')
            ->chunkById(200, function ($students) use ($schoolId, $date, &$created): void {
                $now = now();
                $payload = [];

                foreach ($students as $student) {
                    $payload[] = [
                        'school_id' => $schoolId,
                        'student_id' => $student->id,
                        'classroom_id' => $student->classroom_id,
                        'date' => $date,
                        'status' => Attendance::STATUS_ABSENT,
                        'check_in_at' => null,
                        'check_out_at' => null,
                        'note' => null,
                        'marked_by_user_id' => null,
                        'scanned_by_user_id' => null,
                        'recorded_via' => Attendance::RECORDED_VIA_AUTO_ABSENT,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($payload)) {
                    Attendance::query()->insert($payload);
                    $created += count($payload);
                }
            });

        return $created;
    }

    private function shouldRunForSchool(int $schoolId, Carbon $now, bool $force): bool
    {
        if ($force) {
            return true;
        }

        if ($now->isWeekend()) {
            return false;
        }

        $cutoff = $this->cutoffTimeForSchool($schoolId);
        if ($cutoff === null) {
            return false;
        }

        return $now->format('H:i:s') >= $cutoff;
    }

    private function cutoffTimeForSchool(int $schoolId): ?string
    {
        $settings = TimetableSetting::forSchool($schoolId);
        $value = trim((string) ($settings->auto_absent_cutoff_time ?? ''));

        if ($value === '') {
            $sessionCutoff = collect($settings->attendance_sessions ?? [])
                ->map(fn ($session) => trim((string) data_get($session, 'end', '')))
                ->filter()
                ->sort()
                ->last();
            if ($sessionCutoff) {
                return substr((string) $sessionCutoff, 0, 8);
            }

            return self::CUT_OFF_TIME;
        }

        return substr($value, 0, 8);
    }
}
