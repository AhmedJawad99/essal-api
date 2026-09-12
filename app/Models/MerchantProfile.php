<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantProfile extends Model
{
    protected $fillable = [
        'store_name',
        'store_address',
        'gps_link',
    ];

    //relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
