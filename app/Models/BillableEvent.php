<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillableEvent extends Model
{
    use HasFactory, BelongsToSchool;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'event_date',
        'due_date',
        'amount_per_student',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'event_date' => 'date',
        'due_date' => 'date',
        'amount_per_student' => 'decimal:2',
    ];

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            set: fn ($value) => html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            set: fn ($value) => $value === null ? null : html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        );
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(BillableEventStudent::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillableEventPayment::class);
    }
}
