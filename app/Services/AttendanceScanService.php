<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\TimetableSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceScanService
{
    public function __construct(
        private readonly CardTokenService $cards,
    ) {
    }

    public function process(string $rawValue, User $operator, int $schoolId): array
    {
        $student = $this->resolveStudentFromScan($rawValue, $schoolId);
        $now = now();
        $today = $now->toDateString();

        $attendance = Attendance::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        $computedStatus = $this->statusForCheckIn($now, $schoolId);

        if (!$attendance) {
            $attendance = Attendance::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'classroom_id' => $student->classroom_id,
                'date' => $today,
                'status' => $computedStatus,
                'check_in_at' => $now,
                'marked_by_user_id' => $operator->id,
                'scanned_by_user_id' => $operator->id,
                'recorded_via' => Attendance::RECORDED_VIA_QR,
            ]);

            return $this->payload($attendance->fresh(['student.classroom']), 'check_in', 'Arrivee enregistree avec succes.');
        }

        if (!$attendance->check_in_at) {
            $attendance->fill([
                'classroom_id' => $attendance->classroom_id ?: $student->classroom_id,
                'check_in_at' => $now,
                'scanned_by_user_id' => $operator->id,
                'marked_by_user_id' => $attendance->marked_by_user_id ?: $operator->id,
                'recorded_via' => Attendance::RECORDED_VIA_QR,
            ]);

            if (in_array($attendance->status, [Attendance::STATUS_PRESENT, Attendance::STATUS_ABSENT], true)) {
                $attendance->status = $computedStatus;
            }

            $attendance->save();

            return $this->payload($attendance->fresh(['student.classroom']), 'check_in', 'Arrivee enregistree sur un appel existant.');
        }

        if (!$attendance->check_out_at) {
            $attendance->fill([
                'check_out_at' => $now,
                'scanned_by_user_id' => $operator->id,
            ])->save();

            return $this->payload($attendance->fresh(['student.classroom']), 'check_out', 'Sortie enregistree avec succes.');
        }

        throw ValidationException::withMessages([
            'code' => 'Cette carte a deja ete scannee pour l arrivee et la sortie aujourd hui.',
        ]);
    }

    public function statusForCheckIn(Carbon $time, int $schoolId): string
    {
        $settings = TimetableSetting::forSchool($schoolId);
        $start = Carbon::parse($time->toDateString() . ' ' . substr((string) $settings->day_start_time, 0, 8));
        $graceMinutes = max(0, (int) ($settings->late_grace_minutes ?? 15));
        $graceLimit = $start->copy()->addMinutes($graceMinutes);

        return $time->greaterThan($graceLimit)
            ? Attendance::STATUS_LATE
            : Attendance::STATUS_PRESENT;
    }

    private function resolveStudentFromScan(string $rawValue, int $schoolId): Student
    {
        $parsed = $this->cards->parsePayload($rawValue);
        $type = $parsed['type'];
        $token = trim((string) ($parsed['token'] ?? ''));

        if ($type === 'parent') {
            throw ValidationException::withMessages([
                'code' => 'La carte parent ne peut pas etre utilisee pour un pointage eleve.',
            ]);
        }

        if ($token === '') {
            throw ValidationException::withMessages([
                'code' => 'Le code QR est vide ou invalide.',
            ]);
        }

        $student = Student::query()
            ->where('school_id', $schoolId)
            ->where('card_token', $token)
            ->active()
            ->with('classroom:id,name')
            ->first();

        if (!$student) {
            throw ValidationException::withMessages([
                'code' => 'Aucun eleve de cette ecole ne correspond a ce QR code.',
            ]);
        }

        return $student;
    }

    private function payload(Attendance $attendance, string $action, string $message): array
    {
        $student = $attendance->student;

        return [
            'message' => $message,
            'action' => $action,
            'attendance' => $attendance,
            'record' => [
                'id' => $attendance->id,
                'student_name' => $student?->full_name ?? 'Eleve',
                'classroom_name' => $student?->classroom?->name ?? '-',
                'status' => (string) $attendance->status,
                'status_label' => match ((string) $attendance->status) {
                    Attendance::STATUS_PRESENT => 'Present',
                    Attendance::STATUS_ABSENT => 'Absent',
                    Attendance::STATUS_LATE => 'En retard',
                    default => ucfirst((string) $attendance->status),
                },
                'check_in_at' => optional($attendance->check_in_at)->format('H:i'),
                'check_out_at' => optional($attendance->check_out_at)->format('H:i'),
                'date' => optional($attendance->date)->format('d/m/Y'),
            ],
        ];
    }
}
