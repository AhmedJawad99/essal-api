<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    protected $fillable = [
        'vehicle_type',
        'plate_number',
        'wallet_balance',
        'current_lat',
        'current_lng',
    ];

    //relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
