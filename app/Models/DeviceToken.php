<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    use BelongsToSchool;

    public const PLATFORM_ANDROID = 'android';
    public const PLATFORM_IOS = 'ios';
    public const PLATFORM_WEB = 'web';

    protected $fillable = [
        'user_id',
        'school_id',
        'platform',
        'token',
        'token_hash',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public static function platforms(): array
    {
        return [
            self::PLATFORM_ANDROID,
            self::PLATFORM_IOS,
            self::PLATFORM_WEB,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
