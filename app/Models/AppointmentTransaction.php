<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class AppointmentTransaction extends Model
{
    use BelongsToTenant, PopulateTenantID;

    protected $table = 'appointment_transactions';

    public $fillable = [
        'appointment_id',
        'transaction_type',
        'transaction_id',
        'tenant_id',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
