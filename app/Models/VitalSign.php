<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class VitalSign extends Model
{
    use BelongsToTenant, PopulateTenantID;

    public $table = 'vital_signs';

    public $fillable = [
        'appointment_id',
        'case_id',
        'patient_id',
        'ipd_opd_number',
        'type',
        'blood_pressure',
        'pulse_rate',
        'respiratory_rate',
        'oxygen_saturation',
        'temperature',
        'height',
        'weight',
        'random_blood_sugar',
        'fasting_blood_sugar',
        'drug_allergies',
        'tenant_id',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PatientCase::class, 'case_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
