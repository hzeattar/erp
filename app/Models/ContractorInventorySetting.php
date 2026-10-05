<?php

namespace App\Models;

use App\Traits\HasCompany;

class ContractorInventorySetting extends BaseModel
{
    use HasCompany;

    protected $fillable = ['company_id', 'auto_approve_requests', 'auto_approve_max_quantity'];
    protected $casts = ['auto_approve_requests' => 'boolean', 'auto_approve_max_quantity' => 'integer'];
}
