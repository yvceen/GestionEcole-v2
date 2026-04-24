<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolCalendarEvent extends Model
{
    use BelongsToSchool;

    public const TYPE_EXAM = 'exam';
    public const TYPE_HOLIDAY = 'holiday';
    public const TYPE_EVENT = 'event';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'school_id',
        'created_by_user_id',
        'title',
        'type',
        'starts_on',
        'ends_on',
        'description',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_EXAM,
            self::TYPE_HOLIDAY,
            self::TYPE_EVENT,
            self::TYPE_OTHER,
        ];
    }

    public static function labelForType(string $type): string
    {
        return match ($type) {
            self::TYPE_EXAM => 'Examen',
            self::TYPE_HOLIDAY => 'Vacances',
            self::TYPE_EVENT => 'Evenement',
            default => 'Autre',
        };
    }

    public static function badgeVariant(string $type): string
    {
        return match ($type) {
            self::TYPE_EXAM => 'warning',
            self::TYPE_HOLIDAY => 'success',
            self::TYPE_EVENT => 'info',
            default => 'info',
        };
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
