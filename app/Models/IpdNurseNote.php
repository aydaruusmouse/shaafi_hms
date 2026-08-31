<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class IpdNurseNote extends Model
{
    use BelongsToTenant, PopulateTenantID;

    public $table = 'ipd_nurse_notes';

    public $fillable = [
        'ipd_patient_department_id',
        'nurse_id',
        'note_date',
        'note',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'note_date' => 'datetime',
        ];
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Nurse::class, 'nurse_id');
    }
}
