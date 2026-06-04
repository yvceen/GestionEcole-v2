<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalAuthorization extends Model
{
    use BelongsToSchool;

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'school_id', 'created_by_user_id', 'title', 'category', 'description',
        'instructions', 'event_at', 'due_at', 'status', 'requires_comment',
        'published_at', 'closed_at',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'due_at' => 'datetime',
        'requires_comment' => 'boolean',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function categories(): array
    {
        return [
            'outing' => 'Sortie scolaire',
            'photo' => 'Droit à l’image',
            'medical' => 'Autorisation médicale',
            'transport' => 'Transport',
            'activity' => 'Activité',
            'other' => 'Autre',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DigitalAuthorizationRecipient::class);
    }
}
