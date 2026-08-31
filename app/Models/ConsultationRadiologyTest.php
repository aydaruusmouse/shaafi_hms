<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * App\Models\ConsultationRadiologyTest
 *
 * @property int $id
 * @property int $patient_id
 * @property int $radiology_category_id
 * @property string $caseable_type
 * @property int $caseable_id
 * @property string $test_name
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $caseable
 * @property-read \App\Models\RadiologyCategory $radiologyCategory
 * @property-read \App\Models\Patient $patient
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest query()
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest whereCaseableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest whereCaseableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest whereRadiologyCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest whereTestName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationRadiologyTest whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ConsultationRadiologyTest extends Model
{
    use BelongsToTenant, PopulateTenantID;

    protected $table = 'consultation_radiology_tests';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'radiology_category_id',
        'caseable_type',
        'caseable_id',
        'test_name',
        'notes',
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'patient_id' => 'integer',
        'radiology_category_id' => 'integer',
        'caseable_id' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function radiologyCategory(): BelongsTo
    {
        return $this->belongsTo(RadiologyCategory::class);
    }

    public function chargeCategory(): BelongsTo
    {
        return $this->belongsTo(ChargeCategory::class);
    }

    // FIX: Changed return type from BelongsTo to HasOne
    public function radiologyTest(): HasOne
    {
        return $this->hasOne(RadiologyTest::class, 'consultation_radiology_test_id');
    }

    public function caseable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getSectionAttribute($value)
    {
        if ($value) {
            return $value;
        }
        
        // Infer from caseable_type
        if ($this->caseable_type === 'App\Models\OpdPrescription') {
            return 'OPD';
        } elseif ($this->caseable_type === 'App\Models\IpdPrescription') {
            return 'IPD';
        }
        
        return null;
    }

    /**
     * Scope for OPD tests
     */
    public function scopeOpd($query)
    {
        return $query->where('section', 'OPD')
            ->orWhere('caseable_type', 'App\Models\OpdPrescription');
    }

    /**
     * Scope for IPD tests
     */
    public function scopeIpd($query)
    {
        return $query->where('section', 'IPD')
            ->orWhere('caseable_type', 'App\Models\IpdPrescription');
    }

    // Helper method to get doctor name
    public function getDoctorNameAttribute(): string
    {
        if ($this->caseable_type === IpdPrescription::class) {
            return $this->caseable->doctor->user->full_name ?? 'N/A';
        } elseif ($this->caseable_type === OpdPrescription::class) {
            return $this->caseable->doctor->user->full_name ?? 'N/A';
        }
        
        return 'N/A';
    }
}