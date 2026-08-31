<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class PrescriptionMedicineModal
 *
 * @version August 2, 2022, 4:47 pm UTC
 *
 * @property int $prescription_id
 * @property int $medicie
 * @property varchar $dosage
 * @property varchar $day
 * @property varchar $time
 * @property varchar $comment
 */
class PrescriptionMedicineModal extends Model
{
    use HasFactory;

    public $table = 'prescriptions_medicines';

    public $fillable = [
        'id',
        'prescription_id',
        'medicine',
        'dosage',
        'day',
        'time',
        'dose_interval',
        'comment',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prescription_id' => 'integer',
            'medicine' => 'integer',
            'dosage' => 'string',
            'day' => 'string',
            'time' => 'string',
            'comment' => 'string',
        ];
    }

    public function prescription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function medicines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Medicine::class, 'id', 'medicine');
    }
}
