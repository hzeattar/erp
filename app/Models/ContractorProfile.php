<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractorProfile extends BaseModel
{
    use HasCompany;

    protected $fillable = [
        'company_id', 'user_id', 'name', 'company_name', 'phone', 'email',
        'auto_approve_requests', 'is_active', 'notes',
    ];

    protected $casts = [
        'auto_approve_requests' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function warehouses(): HasMany { return $this->hasMany(ContractorWarehouse::class, 'contractor_id'); }
    public function assignments(): HasMany { return $this->hasMany(ContractorProjectAssignment::class, 'contractor_id'); }
    public function allowances(): HasMany { return $this->hasMany(ContractorMaterialAllowance::class, 'contractor_id'); }
    public function requests(): HasMany { return $this->hasMany(ContractorStockRequest::class, 'contractor_id'); }
    public function movements(): HasMany { return $this->hasMany(ContractorInventoryMovement::class, 'contractor_id'); }
}
