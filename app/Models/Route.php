<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
{
    use SoftDeletes, BelongsToSchool;

    protected $table = 'routes';

    protected $fillable = [
        'school_id',
        'route_name',
        'vehicle_id',
        'start_point',
        'end_point',
        'distance_km',
        'monthly_fee',
        'estimated_minutes',
        'stops',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'distance_km' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'stops' => 'array',
        'estimated_minutes' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TransportAssignment::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('stop_order');
    }

    public function transportLogs(): HasMany
    {
        return $this->hasMany(TransportLog::class);
    }

    public function activeStudents()
    {
        return $this->assignments()->where('is_active', true)->get()->pluck('student');
    }
}
