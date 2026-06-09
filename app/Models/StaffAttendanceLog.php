<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendanceLog extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'employee_name',
        'department_code',
        'department_name',
        'punched_at',
        'punch_date',
        'punch_time',
        'verify_type',
        'punch_state',
        'work_code',
        'card_number',
        'area_name',
        'terminal_alias',
        'terminal_sn',
        'source_file',
        'raw_line',
        'imported_at',
        'metadata',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
        'punch_date' => 'date',
        'imported_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
