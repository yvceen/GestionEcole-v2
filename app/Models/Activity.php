<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use BelongsToSchool;

    public const TYPE_SPORT = 'sport';
    public const TYPE_SORTIE = 'sortie';
    public const TYPE_CONCOURS = 'concours';
    public const TYPE_ATELIER = 'atelier';
    public const TYPE_EVENEMENT = 'evenement';
    public const TYPE_CLUB = 'club';

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'type',
        'start_date',
        'end_date',
        'classroom_id',
        'teacher_id',
        'color',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_SPORT,
            self::TYPE_SORTIE,
            self::TYPE_CONCOURS,
            self::TYPE_ATELIER,
            self::TYPE_EVENEMENT,
            self::TYPE_CLUB,
        ];
    }

    public static function labelForType(string $type): string
    {
        return match ($type) {
            self::TYPE_SPORT => 'Sport',
            self::TYPE_SORTIE => 'Sortie',
            self::TYPE_CONCOURS => 'Concours',
            self::TYPE_ATELIER => 'Atelier',
            self::TYPE_EVENEMENT => 'Evenement',
            self::TYPE_CLUB => 'Club',
            default => ucfirst($type),
        };
    }

    public static function defaultColorForType(string $type): string
    {
        return match ($type) {
            self::TYPE_SPORT => '#0f766e',
            self::TYPE_SORTIE => '#2563eb',
            self::TYPE_CONCOURS => '#9333ea',
            self::TYPE_ATELIER => '#d97706',
            self::TYPE_EVENEMENT => '#dc2626',
            self::TYPE_CLUB => '#0891b2',
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

    public function participants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ActivityReport::class);
    }
}
