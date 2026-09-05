<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class DoctorDailyTicket extends Model
{
    use BelongsToTenant, PopulateTenantID;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_TAKEN = 'taken';

    public const STATUS_CANCELLED = 'cancelled';

    public $table = 'doctor_daily_tickets';

    public $fillable = [
        'doctor_id',
        'ticket_date',
        'ticket_number',
        'status',
        'patient_id',
        'appointment_id',
        'reserved_until',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'ticket_date' => 'date',
            'ticket_number' => 'integer',
            'reserved_until' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_AVAILABLE => __('messages.appointment.ticket_available'),
            self::STATUS_TAKEN => __('messages.appointment.ticket_taken_status'),
            self::STATUS_CANCELLED => __('messages.appointment.ticket_cancelled'),
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function isSelectable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
