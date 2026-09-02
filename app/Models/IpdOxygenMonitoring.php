<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class IpdOxygenMonitoring extends Model
{
    use BelongsToTenant, PopulateTenantID;

    public $table = 'ipd_oxygen_monitorings';

    const DEVICE_ROOM_AIR = 'room_air';

    const DEVICE_NASAL_CANNULA = 'nasal_cannula';

    const DEVICE_SIMPLE_MASK = 'simple_mask';

    const DEVICE_VENTURI_MASK = 'venturi_mask';

    const DEVICE_NON_REBREATHER = 'non_rebreather';

    const DEVICE_HFNC = 'hfnc';

    const DEVICE_NIV = 'niv';

    const DEVICE_VENTILATOR = 'ventilator';

    const TARGET_STANDARD = '94-98';

    const TARGET_COPD = '88-92';

    public $fillable = [
        'ipd_patient_department_id',
        'recorded_at',
        'spo2',
        'delivery_device',
        'flow_rate',
        'fio2',
        'respiratory_rate',
        'target_spo2',
        'nurse_id',
        'notes',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'spo2' => 'integer',
            'flow_rate' => 'decimal:2',
            'fio2' => 'integer',
            'respiratory_rate' => 'integer',
        ];
    }

    public static function deviceOptions(): array
    {
        return [
            self::DEVICE_ROOM_AIR => __('messages.ipd_oxygen_monitoring.room_air'),
            self::DEVICE_NASAL_CANNULA => __('messages.ipd_oxygen_monitoring.nasal_cannula'),
            self::DEVICE_SIMPLE_MASK => __('messages.ipd_oxygen_monitoring.simple_mask'),
            self::DEVICE_VENTURI_MASK => __('messages.ipd_oxygen_monitoring.venturi_mask'),
            self::DEVICE_NON_REBREATHER => __('messages.ipd_oxygen_monitoring.non_rebreather'),
            self::DEVICE_HFNC => __('messages.ipd_oxygen_monitoring.hfnc'),
            self::DEVICE_NIV => __('messages.ipd_oxygen_monitoring.niv'),
            self::DEVICE_VENTILATOR => __('messages.ipd_oxygen_monitoring.ventilator'),
        ];
    }

    public static function targetOptions(): array
    {
        return [
            self::TARGET_STANDARD => __('messages.ipd_oxygen_monitoring.target_standard'),
            self::TARGET_COPD => __('messages.ipd_oxygen_monitoring.target_copd'),
        ];
    }

    public static function spo2Color(?int $spo2): string
    {
        if ($spo2 === null) {
            return 'gray';
        }

        if ($spo2 >= 94) {
            return 'success';
        }

        if ($spo2 >= 88) {
            return 'warning';
        }

        return 'danger';
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Nurse::class, 'nurse_id');
    }

    public function ipdPatientDepartment(): BelongsTo
    {
        return $this->belongsTo(IpdPatientDepartment::class, 'ipd_patient_department_id');
    }
}
