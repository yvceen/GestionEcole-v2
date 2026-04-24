<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouteStop extends Model
{
    protected $fillable = [
        'route_id',
        'name',
        'lat',
        'lng',
        'stop_order',
        'scheduled_time',
        'notes',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'stop_order' => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function transportLogs(): HasMany
    {
        return $this->hasMany(TransportLog::class, 'route_stop_id');
    }
}
