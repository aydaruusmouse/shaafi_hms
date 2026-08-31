<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class IpdOperation extends Model
{
    use BelongsToTenant, PopulateTenantID;

    public $table = 'ipd_operations';

    public $fillable = [
        'ipd_patient_department_id',
        'operation_name',
        'operation_date',
        'surgeon_id',
        'assistant_id',
        'anesthetist_id',
        'notes',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'operation_date' => 'datetime',
        ];
    }

    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'surgeon_id');
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'assistant_id');
    }

    public function anesthetist(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'anesthetist_id');
    }
}
