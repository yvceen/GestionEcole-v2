<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequest extends Model
{
    use BelongsToSchool;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'school_id', 'student_id', 'parent_user_id', 'requested_by_user_id', 'type',
        'custom_type', 'purpose', 'copies', 'language', 'delivery_method', 'status',
        'admin_note', 'rejection_reason', 'processed_by_user_id', 'processed_at',
        'ready_at', 'delivered_at', 'file_path', 'original_name', 'mime_type', 'size_bytes',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public static function types(): array
    {
        return [
            'school_certificate' => 'Certificat de scolarité',
            'enrollment_certificate' => 'Attestation d’inscription',
            'attendance_certificate' => 'Attestation d’assiduité',
            'grade_transcript' => 'Relevé de notes',
            'payment_certificate' => 'Attestation de paiement',
            'transfer_file' => 'Dossier de transfert',
            'other' => 'Autre document',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En préparation',
            self::STATUS_READY => 'Prête',
            self::STATUS_DELIVERED => 'Remise',
            self::STATUS_REJECTED => 'Refusée',
            self::STATUS_CANCELLED => 'Annulée',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'other' && $this->custom_type
            ? $this->custom_type
            : (self::types()[$this->type] ?? $this->type);
    }
}
