<?php

namespace App\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Class SaveTenantID
 */
trait PopulateTenantID
{
    protected static function booted()
    {

        if (Auth::check() && ! empty(Auth::user()->tenant_id)) {

            // static::addGlobalScope('tenant_id', function (Builder $builder) {
            //     $builder->where('tenant_id', Auth::user()->tenant_id);
            // });

            static::saving(function ($modal) {
                $modal->tenant_id = Auth::user()->tenant_id;
            });
        } else {
            static::saving(function ($modal) {
                $modal->tenant_id = $modal->tenant_id;
            });
        }
    }
}
