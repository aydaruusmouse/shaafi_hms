<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * App\Models\PathologyUnit
 *
 * @property int $id
 * @property string $name
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MultiTenant|null $tenant
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyUnit whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PathologyUnit whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PathologyUnit extends Model
{
    use BelongsToTenant, PopulateTenantID;

    public $table = 'pathology_units';

    public $fillable = [
        'name',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|is_unique:pathology_units,name',
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
            'name' => 'string',
        ];
    }
}
