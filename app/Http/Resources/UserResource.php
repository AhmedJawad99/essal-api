<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,

            // جلب بيانات التاجر إذا كان المستخدم تاجراً وتم تحميل العلاقة
            'merchant_profile' => $this->whenLoaded('merchantProfile'),

            // جلب بيانات السائق إذا كان المستخدم سائقاً وتم تحميل العلاقة
            'driver_profile' => $this->whenLoaded('driverProfile'),

            // جلب بيانات المدير إذا كان المستخدم مديراً وتم تحميل العلاقة
            'admin_profile' => $this->whenLoaded('adminProfile'),
        ];
    }
}
