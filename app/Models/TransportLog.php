<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportLog extends Model
{
    use BelongsToSchool;

    public const STATUS_BOARDED = 'boarded';
    public const STATUS_DROPPED = 'dropped';

    protected $fillable = [
        'school_id',
        'transport_assignment_id',
        'student_id',
        'route_id',
        'vehicle_id',
        'route_stop_id',
        'status',
        'recorded_by_user_id',
        'logged_at',
        'note',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [self::STATUS_BOARDED, self::STATUS_DROPPED];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TransportAssignment::class, 'transport_assignment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'route_stop_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
