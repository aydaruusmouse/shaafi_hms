<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * App\Models\PathologyParameter
 *
 * @property int $id
 * @property string $test_name
 * @property string $short_name
 * @property string $test_type
 * @property int $category_id
 * @property int|null $unit
 * @property string|null $subcategory
 * @property string|null $method
 * @property int|null $report_days
 * @property int $charge_category_id
 * @property int $standard_charge
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PathologyUnit|null $pathologyUnit
 * @property-read \App\Models\MultiTenant|null $tenant
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter query()
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereChargeCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereReportDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereShortName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereStandardCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereSubcategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereTestName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereTestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyParameter whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PathologyParameter extends Model
{
    use BelongsToTenant, PopulateTenantID;

    public $table = 'pathology_parameters';

    public $fillable = [
        'parameter_name',
        'reference_range',
        'unit_id',
        'description',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'parameter_name' => 'required|is_unique:pathology_parameters,parameter_name',
        'reference_range' => 'required',
        'unit_id' => 'required',
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
            'parameter_name' => 'string',
            'reference_range' => 'string',
            'unit_id' => 'integer',
            'description' => 'string',
        ];
    }

    public function pathologyUnit(): BelongsTo
    {
        return $this->belongsTo(PathologyUnit::class, 'unit_id');
    }
}
