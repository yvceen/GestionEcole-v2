<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'registration_number',
        'vehicle_type',
        'capacity',
        'driver_id',
        'assistant_name',
        'plate_number',
        'color',
        'model_year',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'model_year' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    public function transportLogs(): HasMany
    {
        return $this->hasMany(TransportLog::class);
    }
}
