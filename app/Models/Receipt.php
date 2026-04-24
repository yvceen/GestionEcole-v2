<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToSchool;


class Receipt extends Model
{
    use BelongsToSchool;
  
    protected $fillable = [
        'school_id',
        'receipt_number',
        'parent_id',
        'method',
        'total_amount',
        'issued_at',
        'received_by_admin_user_id',
        'note',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_admin_user_id');
    }
}
