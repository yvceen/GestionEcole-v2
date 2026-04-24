<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CardTokenService
{
    public function ensureStudentToken(Student $student, bool $force = false): Student
    {
        if ($force || blank($student->card_token)) {
            $student->forceFill([
                'card_token' => $this->generateUniqueToken('STD', fn (string $token): bool => Student::where('card_token', $token)->exists()),
            ])->save();
        }

        return $student->refresh();
    }

    public function ensureParentToken(User $user, bool $force = false): User
    {
        if ($force || blank($user->card_token)) {
            $user->forceFill([
                'card_token' => $this->generateUniqueToken('PAR', fn (string $token): bool => User::where('card_token', $token)->exists()),
            ])->save();
        }

        return $user->refresh();
    }

    public function qrPayloadForStudent(Student $student): string
    {
        return 'MYEDU:STUDENT:' . (string) $student->card_token;
    }

    public function qrPayloadForParent(User $user): string
    {
        return 'MYEDU:PARENT:' . (string) $user->card_token;
    }

    public function qrSvg(string $payload, int $size = 220): string
    {
        $size = max(120, min($size, 600));

        return QrCode::format('svg')
            ->size($size)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($payload);
    }

    public function parsePayload(string $rawValue): array
    {
        $rawValue = trim($rawValue);
        if ($rawValue === '') {
            return ['type' => null, 'token' => null];
        }

        $normalized = strtoupper($rawValue);

        if (str_starts_with($normalized, 'MYEDU:STUDENT:')) {
            return [
                'type' => 'student',
                'token' => substr($rawValue, strlen('MYEDU:STUDENT:')),
            ];
        }

        if (str_starts_with($normalized, 'MYEDU:PARENT:')) {
            return [
                'type' => 'parent',
                'token' => substr($rawValue, strlen('MYEDU:PARENT:')),
            ];
        }

        if (str_starts_with($normalized, 'STD-')) {
            return ['type' => 'student', 'token' => $rawValue];
        }

        if (str_starts_with($normalized, 'PAR-')) {
            return ['type' => 'parent', 'token' => $rawValue];
        }

        return ['type' => null, 'token' => $rawValue];
    }

    private function generateUniqueToken(string $prefix, callable $exists): string
    {
        do {
            $token = $prefix . '-' . $this->randomReadableString(12);
        } while ($exists($token));

        return $token;
    }

    private function randomReadableString(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($alphabet) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $alphabet[random_int(0, $maxIndex)];
        }

        return $result;
    }
}
