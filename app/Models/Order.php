<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    use HasFactory;
    protected $fillable = [
        'region_id',
        'batch_id',

        'tracking_code',
        'customer_name',
        'customer_phone',
        'customer_phone_alt',
        'order_description',
        'type',
        'status',
        'payment_method',
        'is_customer_paid',
        'pickup_address',
        'pickup_gps_link',
        'delivery_address',
        'delivery_gps_link',
        'delivery_cost',
        'total_amount'
    ];

    //relationships

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
    public function orderStatusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function orderBatches()
    {
        return $this->belongsTo(OrderBatch::class, 'batch_id');
    }
}
