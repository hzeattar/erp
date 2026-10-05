<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorInventoryMovement extends BaseModel
{
    use HasCompany;

    protected $fillable = [
        'company_id', 'contractor_id', 'warehouse_id', 'project_id', 'product_id', 'movement_type',
        'quantity', 'reference', 'notes', 'stock_request_id', 'created_by', 'movement_date',
    ];

    protected $casts = ['quantity' => 'integer', 'movement_date' => 'date'];

    public function contractor(): BelongsTo { return $this->belongsTo(ContractorProfile::class, 'contractor_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(ContractorWarehouse::class, 'warehouse_id'); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function request(): BelongsTo { return $this->belongsTo(ContractorStockRequest::class, 'stock_request_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
