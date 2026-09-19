<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        // --- IGNORE ---
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'region_id' => 'required|exists:regions,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_phone_alt' => 'nullable|string|max:20',
            'order_description' => 'nullable|string',
            'type' => 'required|in:pickup,delivery',
            'payment_method' => 'required|in:cash,card,online',
            'pickup_address' => 'nullable|string|max:255',
            'pickup_gps_link' => 'nullable|string|max:255',
            'delivery_address' => 'nullable|string|max:255',
            'delivery_gps_link' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $region = Region::findOrFail($request->region_id)->first();
        $delevery_cost = $region->default_delivery_cost;

        $trackingCode = 'ORD-' . strtoupper(Str::random(10));

        try {
            DB::beginTransaction();
            $order = $request->user()->merchantOrders()->create([
                'region_id' => $request->region_id,
                'tracking_code' => $trackingCode,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_phone_alt' => $request->customer_phone_alt,
                'order_description' => $request->order_description,
                'type' => $request->type,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'is_customer_paid' => false,
                'pickup_address' => $request->pickup_address,
                'pickup_gps_link' => $request->pickup_gps_link,
                'delivery_address' => $request->delivery_address,
                'delivery_gps_link' => $request->delivery_gps_link,
                'delivery_cost' => $delevery_cost,
                'total_amount' => $request->total_amount + $delevery_cost
            ]);

            $logs = $order->orderStatusLogs()->create([
                'changed_by_id' => $region->manager_id,
                'status' => 'pending',
                'changed_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم إنشاء الطلب بنجاح',
                'order' => $order,
                'logs' => $logs
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء الطلب',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
