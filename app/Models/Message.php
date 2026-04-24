<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Message extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'sender_id',
        'sender_role',
        'recipient_type',
        'recipient_id',
        'target_type',
        'target_id',
        'target_user_ids',
        'subject',
        'body',
        'approval_required',
        'thread_id',
        'reply_to_id',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'target_user_ids' => 'array',
        'approval_required' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function threadRoot(): BelongsTo
    {
        return $this->belongsTo(self::class, 'thread_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeInThread(Builder $query, Message|int $message): Builder
    {
        $threadId = $message instanceof self ? $message->thread_key : (int) $message;

        return $query->where(function (Builder $threadQuery) use ($threadId) {
            if (self::hasThreadIdColumn()) {
                $threadQuery->where('thread_id', $threadId);
            }

            $threadQuery->orWhere('id', $threadId);

            if (self::hasReplyToIdColumn()) {
                $threadQuery->orWhere('reply_to_id', $threadId);
            }
        });
    }

    public function scopeWhereThreadKey(Builder $query, int $threadId): Builder
    {
        return $query->where(function (Builder $threadQuery) use ($threadId) {
            if (self::hasThreadIdColumn()) {
                $threadQuery->where('thread_id', $threadId);
            }

            $threadQuery->orWhere('id', $threadId);

            if (self::hasReplyToIdColumn()) {
                $threadQuery->orWhere('reply_to_id', $threadId);
            }
        });
    }

    public function scopeWhereInThreadKeys(Builder $query, array $threadIds): Builder
    {
        $threadIds = array_values(array_unique(array_filter(array_map('intval', $threadIds), fn (int $id) => $id > 0)));

        if ($threadIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $threadQuery) use ($threadIds) {
            if (self::hasThreadIdColumn()) {
                $threadQuery->whereIn('thread_id', $threadIds);
            }

            $threadQuery->orWhereIn('id', $threadIds);

            if (self::hasReplyToIdColumn()) {
                $threadQuery->orWhereIn('reply_to_id', $threadIds);
            }
        });
    }

    public function scopeAddressedToUser(Builder $query, int $userId): Builder
    {
        $columns = self::columns();
        $hasTarget = in_array('target_type', $columns, true) && in_array('target_id', $columns, true);
        $hasRecipient = in_array('recipient_type', $columns, true) && in_array('recipient_id', $columns, true);

        if ($hasTarget) {
            return $query->where('target_type', 'user')
                ->where(function (Builder $inner) use ($userId, $columns) {
                    $inner->where('target_id', $userId);

                    if (in_array('target_user_ids', $columns, true)) {
                        $inner->orWhere(function (Builder $jsonQuery) use ($userId) {
                            $jsonQuery->whereNotNull('target_user_ids')
                                ->whereJsonContains('target_user_ids', $userId);
                        });
                    }
                });
        }

        if ($hasRecipient) {
            return $query->where('recipient_type', 'user')
                ->where('recipient_id', $userId);
        }

        return $query->whereRaw('1 = 0');
    }

    public function getThreadKeyAttribute(): int
    {
        if (self::hasThreadIdColumn() && !empty($this->thread_id)) {
            return (int) $this->thread_id;
        }

        if (self::hasReplyToIdColumn() && !empty($this->reply_to_id)) {
            return (int) $this->reply_to_id;
        }

        return (int) $this->id;
    }

    public function isForUser(int $userId): bool
    {
        if (
            array_key_exists('recipient_type', $this->attributes)
            && array_key_exists('recipient_id', $this->attributes)
        ) {
            return (($this->attributes['recipient_type'] ?? null) === 'user')
                && ((int) ($this->attributes['recipient_id'] ?? 0) === (int) $userId);
        }

        if (($this->target_type ?? null) !== 'user') {
            return false;
        }

        if (!empty($this->target_id) && (int) $this->target_id === (int) $userId) {
            return true;
        }

        $ids = $this->target_user_ids ?? [];

        return in_array((int) $userId, array_map('intval', $ids), true);
    }

    public function bodyText(): string
    {
        return (string) ($this->body ?? $this->message ?? $this->content ?? '');
    }

    public function subjectText(): string
    {
        return (string) ($this->subject ?? $this->title ?? '(Sans sujet)');
    }

    public static function columns(): array
    {
        return Schema::hasTable('messages') ? Schema::getColumnListing('messages') : [];
    }

    public static function hasThreadIdColumn(): bool
    {
        return in_array('thread_id', self::columns(), true);
    }

    public static function hasReplyToIdColumn(): bool
    {
        return in_array('reply_to_id', self::columns(), true);
    }
}
