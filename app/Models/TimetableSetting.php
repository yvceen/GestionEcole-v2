<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TimetableSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'day_start_time',
        'late_grace_minutes',
        'day_end_time',
        'auto_absent_cutoff_time',
        'attendance_sessions',
        'allow_manual_time_override',
        'slot_minutes',
        'lunch_start',
        'lunch_end',
    ];

    protected $casts = [
        'attendance_sessions' => 'array',
        'allow_manual_time_override' => 'boolean',
    ];

    public static function forSchool(int $schoolId): self
    {
        $setting = static::query()
            ->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhereNull('school_id');
            })
            ->orderByRaw('school_id IS NULL')
            ->first();

        if ($setting) {
            return $setting;
        }

        return static::create([
            'school_id' => $schoolId,
            'day_start_time' => '08:00:00',
            'late_grace_minutes' => 15,
            'day_end_time' => '18:00:00',
            'auto_absent_cutoff_time' => '09:00:00',
            'attendance_sessions' => [
                ['label' => 'Matin', 'start' => '08:00', 'end' => '12:00'],
                ['label' => 'Apres-midi', 'start' => '14:00', 'end' => '18:00'],
            ],
            'allow_manual_time_override' => true,
            'slot_minutes' => 60,
        ]);
    }
}
