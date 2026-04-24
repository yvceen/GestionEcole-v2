<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\TimetableSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceEntryScanService
{
    public function __construct(
        private readonly CardTokenService $cards,
        private readonly NotificationService $notifications,
    ) {
    }

    public function scan(string $qrToken, User $operator, int $schoolId): array
    {
        $student = $this->resolveStudent($qrToken, $schoolId);
        $now = now();
        $date = $now->toDateString();

        $existing = Attendance::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->whereDate('date', $date)
            ->first();

        $status = $this->resolveStatus($now, $schoolId);

        if ($existing && $this->canConvertToCheckIn($existing)) {
            $existing->fill([
                'classroom_id' => $existing->classroom_id ?: $student->classroom_id,
                'status' => $status,
                'check_in_at' => $now,
                'marked_by_user_id' => $operator->id,
                'scanned_by_user_id' => $operator->id,
                'recorded_via' => Attendance::RECORDED_VIA_QR,
            ])->save();

            $this->notifyParent($student, $status);

            return [
                'success' => true,
                'duplicate' => false,
                'student_name' => $student->full_name,
                'status' => $status,
                'message' => $status === Attendance::STATUS_LATE
                    ? 'Arrivee confirmee. L absence automatique a ete convertie en retard.'
                    : 'Arrivee confirmee. L absence automatique a ete retiree.',
            ];
        }

        if ($existing) {
            return [
                'success' => true,
                'duplicate' => true,
                'student_name' => $student->full_name,
                'status' => (string) $existing->status,
                'message' => 'Presence deja enregistree pour aujourd hui.',
            ];
        }

        Attendance::create([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'classroom_id' => $student->classroom_id,
            'date' => $date,
            'status' => $status,
            'check_in_at' => $now,
            'marked_by_user_id' => $operator->id,
            'scanned_by_user_id' => $operator->id,
            'recorded_via' => Attendance::RECORDED_VIA_QR,
        ]);

        $this->notifyParent($student, $status);

        return [
            'success' => true,
            'duplicate' => false,
            'student_name' => $student->full_name,
            'status' => $status,
            'message' => $status === Attendance::STATUS_LATE
                ? 'Presence enregistree en retard.'
                : 'Presence enregistree avec succes.',
        ];
    }

    private function resolveStudent(string $qrToken, int $schoolId): Student
    {
        $parsed = $this->cards->parsePayload($qrToken);
        $type = $parsed['type'];
        $token = trim((string) ($parsed['token'] ?? ''));

        if ($type === 'parent') {
            throw ValidationException::withMessages([
                'qr_token' => 'Le QR scanne appartient a un parent.',
            ]);
        }

        if ($token === '') {
            throw ValidationException::withMessages([
                'qr_token' => 'QR token invalide.',
            ]);
        }

        $student = Student::query()
            ->where('school_id', $schoolId)
            ->active()
            ->where('card_token', $token)
            ->first();

        if (!$student) {
            throw ValidationException::withMessages([
                'qr_token' => 'Aucun eleve correspondant dans cette ecole.',
            ]);
        }

        return $student;
    }

    private function resolveStatus(Carbon $time, int $schoolId): string
    {
        $settings = TimetableSetting::forSchool($schoolId);
        $start = Carbon::parse($time->toDateString() . ' ' . substr((string) $settings->day_start_time, 0, 8));
        $graceLimit = $start->copy()->addMinutes(max(0, (int) ($settings->late_grace_minutes ?? 15)));

        return $time->greaterThan($graceLimit)
            ? Attendance::STATUS_LATE
            : Attendance::STATUS_PRESENT;
    }

    private function canConvertToCheckIn(Attendance $attendance): bool
    {
        return !$attendance->check_in_at
            && (string) $attendance->status === Attendance::STATUS_ABSENT;
    }

    private function notifyParent(Student $student, string $status): void
    {
        if (!$student->parent_user_id || !in_array($status, [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE], true)) {
            return;
        }

        $this->notifications->notifyUsers(
            [(int) $student->parent_user_id],
            'attendance',
            $student->full_name,
            $status === Attendance::STATUS_LATE
                ? 'Your child is late today'
                : 'Your child is present today',
            [
                'student_id' => $student->id,
                'school_id' => $student->school_id,
                'status' => $status,
                'date' => now()->toDateString(),
                'route' => route('parent.attendance.index', absolute: false),
            ]
        );
    }
}
