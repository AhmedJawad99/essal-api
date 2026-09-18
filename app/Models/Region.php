<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $fillable = [
        'name',
        'default_delivery_cost'
    ];

    public function merchants()
    {
        return $this->hasMany(MerchantProfile::class);
    }

    public function drivers()
    {
        return $this->hasMany(DriverProfile::class);
    }
}
