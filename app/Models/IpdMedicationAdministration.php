<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class IpdMedicationAdministration extends Model
{
    use BelongsToTenant, PopulateTenantID;

    public $table = 'ipd_medication_administrations';

    const STATUS_GIVEN = 'given';

    const STATUS_HELD = 'held';

    const STATUS_REFUSED = 'refused';

    const STATUS_MISSED = 'missed';

    public $fillable = [
        'ipd_patient_department_id',
        'ipd_prescription_item_id',
        'medicine_id',
        'medicine_name',
        'dosage',
        'given_at',
        'nurse_id',
        'status',
        'notes',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'given_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_GIVEN => __('messages.ipd_patient_mar.given'),
            self::STATUS_HELD => __('messages.ipd_patient_mar.held'),
            self::STATUS_REFUSED => __('messages.ipd_patient_mar.refused'),
            self::STATUS_MISSED => __('messages.ipd_patient_mar.missed'),
        ];
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Nurse::class, 'nurse_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(IpdPrescriptionItem::class, 'ipd_prescription_item_id');
    }
}
