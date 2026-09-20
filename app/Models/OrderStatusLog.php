<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusLog extends Model
{
    protected $fillable = [
        'changed_by_id',
        'status',
        'location_lat',
        'location_lng',
        'note'
    ];

    //relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
