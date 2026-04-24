<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use BelongsToSchool;

    public const TYPE_COURSE = 'course';
    public const TYPE_EXAM = 'exam';
    public const TYPE_ACTIVITY = 'activity';

    protected $fillable = [
        'school_id',
        'title',
        'type',
        'start',
        'end',
        'classroom_id',
        'teacher_id',
        'color',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_COURSE,
            self::TYPE_EXAM,
            self::TYPE_ACTIVITY,
        ];
    }

    public static function labelForType(string $type): string
    {
        return match ($type) {
            self::TYPE_COURSE => 'Cours',
            self::TYPE_EXAM => 'Examen',
            self::TYPE_ACTIVITY => 'Activite',
            default => ucfirst($type),
        };
    }

    public static function defaultColorForType(string $type): string
    {
        return match ($type) {
            self::TYPE_COURSE => '#0f766e',
            self::TYPE_EXAM => '#dc2626',
            self::TYPE_ACTIVITY => '#2563eb',
            default => '#475569',
        };
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
