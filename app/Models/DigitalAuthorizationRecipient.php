<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAuthorizationRecipient extends Model
{
    use BelongsToSchool;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'school_id', 'digital_authorization_id', 'student_id', 'parent_user_id',
        'status', 'response_comment', 'signed_name', 'responded_at',
        'response_ip', 'response_user_agent',
    ];

    protected $casts = ['responded_at' => 'datetime'];

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(DigitalAuthorization::class, 'digital_authorization_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }
}
