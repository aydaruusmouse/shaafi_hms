<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class PatientQueue extends Model
{
    use BelongsToTenant, PopulateTenantID;

    protected $table = 'patient_queues';

    protected $fillable = [
        'no',
        'appointment_id',
        'tenant_id'
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
