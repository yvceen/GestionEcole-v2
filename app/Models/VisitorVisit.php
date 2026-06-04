<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorVisit extends Model
{
    use BelongsToSchool;

    public const STATUS_EXPECTED = 'expected';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'school_id', 'visitor_name', 'phone', 'identity_type', 'identity_number',
        'organization', 'vehicle_plate', 'purpose', 'purpose_details', 'host_user_id',
        'student_id', 'expected_at', 'checked_in_at', 'checked_out_at', 'status',
        'badge_code', 'entry_note', 'exit_note', 'created_by_user_id',
        'checked_in_by_user_id', 'checked_out_by_user_id',
    ];

    protected $casts = [
        'expected_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public static function purposes(): array
    {
        return [
            'parent_meeting' => 'Rendez-vous parent',
            'administration' => 'Démarche administrative',
            'supplier' => 'Fournisseur ou livraison',
            'maintenance' => 'Maintenance ou intervention',
            'recruitment' => 'Entretien ou recrutement',
            'event' => 'Événement scolaire',
            'other' => 'Autre visite',
        ];
    }

    public function hostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by_user_id');
    }
}
