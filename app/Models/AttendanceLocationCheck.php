<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLocationCheck extends BaseModel
{
    use HasCompany;

    protected $fillable = [
        'company_id',
        'attendance_id',
        'user_id',
        'branch_id',
        'event_type',
        'status',
        'latitude',
        'longitude',
        'accuracy_in_meters',
        'distance_in_meters',
        'allowed_radius_in_meters',
        'failure_reason',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_in_meters' => 'float',
        'distance_in_meters' => 'float',
        'allowed_radius_in_meters' => 'integer',
        'occurred_at' => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
