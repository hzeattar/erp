<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends BaseModel
{
    use HasCompany;

    protected $fillable = [
        'company_id',
        'name',
        'latitude',
        'longitude',
        'allowed_radius_in_meters',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'allowed_radius_in_meters' => 'integer',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
