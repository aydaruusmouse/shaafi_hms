<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Eloquent as Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * Class DoctorDepartment
 *
 * @version February 21, 2020, 5:23 am UTC
 *
 * @property string title
 * @property string description
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|DoctorDepartment newModelQuery()
 * @method static Builder|DoctorDepartment newQuery()
 * @method static Builder|DoctorDepartment query()
 * @method static Builder|DoctorDepartment whereCreatedAt($value)
 * @method static Builder|DoctorDepartment whereDescription($value)
 * @method static Builder|DoctorDepartment whereId($value)
 * @method static Builder|DoctorDepartment whereTitle($value)
 * @method static Builder|DoctorDepartment whereUpdatedAt($value)
 *
 * @mixin Model
 *
 * @property-read Collection|Doctor[] $doctors
 * @property-read int|null $doctors_count
 * @property int $is_default
 *
 * @method static Builder|DoctorDepartment whereIsDefault($value)
 */
class DoctorDepartment extends Model
{
    use BelongsToTenant, PopulateTenantID;

    public $table = 'doctor_departments';

    public $fillable = [
        'title',
        'tenant_id',
        'description',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'title' => 'required|is_unique:doctor_departments,title',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'title' => 'string',
            'description' => 'string',
        ];
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class, 'doctor_department_id');
    }
}
