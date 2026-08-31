<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * App\Models\ConsultationMedicalInformation
 *
 * @property int $id
 * @property int $patient_id
 * @property string $caseable_type
 * @property int $caseable_id
 * @property string|null $chief_complain
 * @property string|null $past_medical_surgical_history
 * @property string|null $family_social_history
 * @property string|null $drug_history_allergy
 * @property string|null $chronic_diseases_history
 * @property string|null $obstetric_gynecology_history
 * @property string|null $physical_examination
 * @property string|null $differential_diagnosis
 * @property string|null $professional_diagnosis
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $caseable
 * @property-read \App\Models\Patient $patient
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation query()
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereCaseableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereCaseableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereChiefComplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereChronicDiseasesHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereDifferentialDiagnosis($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereDrugHistoryAllergy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereFamilySocialHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereObstetricGynecologyHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation wherePastMedicalSurgicalHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation wherePhysicalExamination($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereProfessionalDiagnosis($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ConsultationMedicalInformation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ConsultationMedicalInformation extends Model
{
    use BelongsToTenant, PopulateTenantID;

    protected $table = 'consultation_medical_informations';

    protected $fillable = [
        'patient_id',
        'caseable_type',
        'caseable_id',
        'chief_complain',
        'past_medical_surgical_history',
        'family_social_history',
        'drug_history_allergy',
        'chronic_diseases_history',
        'obstetric_gynecology_history',
        'physical_examination',
        'differential_diagnosis',
        'professional_diagnosis',
    ];

    protected $casts = [
        'id' => 'integer',
        'patient_id' => 'integer',
        'caseable_id' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function caseable(): MorphTo
    {
        return $this->morphTo();
    }
}