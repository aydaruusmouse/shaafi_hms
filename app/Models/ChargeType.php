<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeType extends Model
{

    public $table = 'charge_types';

    public $fillable = [
        'name',
        'tenant_id',
    ];

    public static $rules = [
        'name' => 'required|string|max:255',
    ];
}
