<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Class SaveTenantID
 */
trait PopulateTenantID
{
    protected static function booted()
    {
        static::saving(function ($modal) {
            if (Auth::check() && ! empty(Auth::user()->tenant_id)) {
                $modal->tenant_id = Auth::user()->tenant_id;
            }
        });
    }
}
