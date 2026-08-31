<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class HospitalType
 *
 * @version September 5, 2022, 8:14 pm UTC
 *
 * @property string $type
 */
class HospitalType extends Model
{
    //    use SoftDeletes;

    use HasFactory;

    public $table = 'hospital_type';

    //    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at', 'updated_at'];

    public $fillable = [
        'id',
        'name',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|unique:hospital_type,name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'string',
        ];
    }

    public function prepareHospitalType()
    {
        return [
            'id' => $this->id ?? __('messages.common.n/a'),
            'name' => $this->name ?? __('messages.common.n/a'),
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'hospital_type_id');
    }

    public function hospitalCount()
    {
        return $this->user()->count();
    }
}
