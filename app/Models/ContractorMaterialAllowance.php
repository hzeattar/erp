<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorMaterialAllowance extends BaseModel
{
    use HasCompany;

    protected $fillable = ['company_id', 'contractor_id', 'project_id', 'product_id', 'allowed_quantity', 'issued_quantity', 'auto_approve', 'is_active', 'notes'];
    protected $casts = ['allowed_quantity' => 'integer', 'issued_quantity' => 'integer', 'auto_approve' => 'boolean', 'is_active' => 'boolean'];

    public function contractor(): BelongsTo { return $this->belongsTo(ContractorProfile::class, 'contractor_id'); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
