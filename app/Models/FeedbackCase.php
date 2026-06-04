<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackCase extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'submitted_by_user_id', 'student_id', 'reference', 'kind',
        'category', 'subject', 'description', 'priority', 'status', 'is_confidential',
        'assigned_to_user_id', 'closed_by_user_id', 'first_response_at',
        'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'is_confidential' => 'boolean',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function kinds(): array
    {
        return ['complaint' => 'Réclamation', 'suggestion' => 'Suggestion', 'appreciation' => 'Remerciement'];
    }

    public static function categories(): array
    {
        return [
            'education' => 'Pédagogie et apprentissage',
            'school_life' => 'Vie scolaire',
            'administration' => 'Administration',
            'finance' => 'Finance et paiements',
            'transport' => 'Transport scolaire',
            'safety' => 'Sécurité et bien-être',
            'communication' => 'Communication',
            'other' => 'Autre',
        ];
    }

    public static function statuses(): array
    {
        return ['new' => 'Nouvelle', 'reviewing' => 'En étude', 'waiting_submitter' => 'Réponse attendue', 'resolved' => 'Résolue', 'closed' => 'Clôturée'];
    }

    public static function priorities(): array
    {
        return ['low' => 'Faible', 'normal' => 'Normale', 'high' => 'Élevée', 'urgent' => 'Urgente'];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FeedbackCaseMessage::class);
    }
}
