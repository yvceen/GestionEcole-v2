<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PaymentReminderSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'is_enabled',
        'reminder_day',
        'message_template',
        'last_sent_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'reminder_day' => 'integer',
        'last_sent_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
