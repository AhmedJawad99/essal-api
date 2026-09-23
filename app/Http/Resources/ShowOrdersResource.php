<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowOrdersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_code' => $this->tracking_code,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_phone_alt' => $this->customer_phone_alt,
            'order_description' => $this->order_description,
            'status' => $this->status,
            'batch_code' => $this->batch_code,
            'type' => $this->type,
            'payment_method' => $this->payment_method,
            'pickup_address' => $this->pickup_address,
            'pickup_gps_link' => $this->pickup_gps_link,
            'delivery_address' => $this->delivery_address,
            'delivery_gps_link' => $this->delivery_gps_link,
            'total_amount' => $this->total_amount,
            'region_id' => $this->region_id,
            'region_name' => $this->region ? $this->region->name : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
