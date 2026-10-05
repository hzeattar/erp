<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorProjectAssignment extends BaseModel
{
    use HasCompany;

    protected $fillable = ['company_id', 'contractor_id', 'project_id', 'responsibility', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function contractor(): BelongsTo { return $this->belongsTo(ContractorProfile::class, 'contractor_id'); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
