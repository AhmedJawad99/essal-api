<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBatch extends Model
{
    protected $fillable = [
        'region_id',
        'driver_id',
        'merchant_id',
        'batch_code',
        'status',
    ];


    //relationships
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'batch_id');
    }
}
