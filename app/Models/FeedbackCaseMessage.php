<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackCaseMessage extends Model
{
    protected $fillable = ['feedback_case_id', 'user_id', 'message', 'is_internal'];

    protected $casts = ['is_internal' => 'boolean'];

    public function feedbackCase(): BelongsTo
    {
        return $this->belongsTo(FeedbackCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
