<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBehavior extends Model
{
    use BelongsToSchool;

    public const TYPE_RETARD = 'retard';
    public const TYPE_COMPORTEMENT = 'comportement';
    public const TYPE_SANCTION = 'sanction';
    public const TYPE_REMARQUE = 'remarque';

    protected $fillable = [
        'school_id',
        'student_id',
        'type',
        'description',
        'visible_to_parent',
        'created_by_user_id',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'visible_to_parent' => 'boolean',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_RETARD,
            self::TYPE_COMPORTEMENT,
            self::TYPE_SANCTION,
            self::TYPE_REMARQUE,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
