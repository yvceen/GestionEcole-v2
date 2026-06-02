<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillableEventStudent extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'billable_event_id',
        'student_id',
        'amount_due',
        'is_exempt',
        'exemption_reason',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'is_exempt' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(BillableEvent::class, 'billable_event_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillableEventPayment::class, 'student_id', 'student_id')
            ->whereColumn('billable_event_payments.billable_event_id', 'billable_event_students.billable_event_id');
    }
}
