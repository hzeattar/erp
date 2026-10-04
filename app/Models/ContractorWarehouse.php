<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractorWarehouse extends BaseModel
{
    use HasCompany;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(ContractorInventoryStock::class, 'warehouse_id');
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(ContractorStockTransfer::class, 'from_warehouse_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(ContractorStockTransfer::class, 'to_warehouse_id');
    }
}
