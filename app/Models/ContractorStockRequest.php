<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorStockRequest extends BaseModel
{
    use HasCompany;

    protected $fillable = [
        'company_id', 'contractor_id', 'project_id', 'from_warehouse_id', 'to_warehouse_id',
        'product_id', 'requested_quantity', 'approved_quantity', 'status', 'was_auto_approved',
        'request_notes', 'decision_notes', 'requested_by', 'decided_by', 'decided_at', 'issued_at', 'issued_by',
    ];

    protected $casts = [
        'requested_quantity' => 'integer', 'approved_quantity' => 'integer', 'was_auto_approved' => 'boolean',
        'decided_at' => 'datetime', 'issued_at' => 'datetime',
    ];

    public function contractor(): BelongsTo { return $this->belongsTo(ContractorProfile::class, 'contractor_id'); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function fromWarehouse(): BelongsTo { return $this->belongsTo(ContractorWarehouse::class, 'from_warehouse_id'); }
    public function toWarehouse(): BelongsTo { return $this->belongsTo(ContractorWarehouse::class, 'to_warehouse_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function decider(): BelongsTo { return $this->belongsTo(User::class, 'decided_by'); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
}
