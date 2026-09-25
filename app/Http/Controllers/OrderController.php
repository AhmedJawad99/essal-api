<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShowOrdersResource;
use App\Models\Order;
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

    public function showByTrackingCode($trackingCode, Request $request)
    {

        $user = $request->user();
        // merchant
        if ($user->hasRole('merchant') || $user->role === 'merchant') {
            $order = $user->merchantOrders()->where('tracking_code', $trackingCode)->with('region')->first();

            return $order ? new ShowOrdersResource($order) : response()->json(['message' => 'Order not found'], 404);
        }
        // driver
        elseif ($user->hasRole('driver') || $user->role === 'driver') {
            $order = $user->driverOrders()->where('tracking_code', $trackingCode)->with('region')->first();

            return $order ? new ShowOrdersResource($order) : response()->json(['message' => 'Order not found'], 404);
        }
        // admin
        elseif ($user->hasRole('admin') || $user->role === 'admin') {
            // super admin
            if ($user->adminProfile && $user->adminProfile->is_super_admin) {
                $order = Order::where('tracking_code', $trackingCode)->with(['region', 'merchant', 'driver'])->first();

                return $order ? new ShowOrdersResource($order) : response()->json(['message' => 'Order not found'], 404);
            }
            // manager admin
            elseif ($user->adminProfile && $user->adminProfile->region_id) {
                $order = Order::where('tracking_code', $trackingCode)
                    ->where('region_id', $user->adminProfile->region_id)
                    ->with(['region', 'merchant', 'driver'])
                    ->first();

                return $order ? new ShowOrdersResource($order) : response()->json(['message' => 'Order not found'], 404);
            } else {
                return response()->json(['message' => ''], 403);
            }
        } else {
            return response()->json([
                'message' => 'You are not authorized to view this order',
            ], 403);
        }
    }

    public function showOrders(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('merchant') || $user->role === 'merchant') {
            $orders = $user->merchantOrders()->with('region')->orderBy('created_at', 'desc')->paginate(20);

            return ShowOrdersResource::collection($orders);
        } elseif ($user->hasRole('driver') || $user->role === 'driver') {
            $orders = $user->driverOrders()->with('region')->orderBy('created_at', 'desc')->paginate(20);

            return ShowOrdersResource::collection($orders);
        } elseif ($user->hasRole('admin') || $user->role === 'admin') {

            if ($user->adminProfile && $user->adminProfile->is_super_admin) {
                $orders = Order::with(['region', 'merchant', 'driver'])->orderBy('created_at', 'desc')->paginate(20);

                return ShowOrdersResource::collection($orders);
            }

            if ($user->adminProfile && $user->adminProfile->region_id) {
                $orders = Order::where('region_id', $user->adminProfile->region_id)
                    ->with('region')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);

                return ShowOrdersResource::collection($orders);
            }

            return response()->json(['message' => 'حساب الإدارة هذا غير مكتمل الإعدادات'], 403);
        }

        return response()->json([
            'message' => 'You are not authorized to view orders',
        ], 403);
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
            'total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $region = Region::findOrFail($request->region_id);
        $delevery_cost = $region->default_delivery_cost;

        $trackingCode = 'ORD-'.strtoupper(Str::random(10));

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
                'total_amount' => $request->total_amount + $delevery_cost,
            ]);

            $logs = $order->orderStatusLogs()->create([
                'changed_by_id' => Auth::id(),
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم إنشاء الطلب بنجاح',
                'order' => $order,
                'logs' => $logs,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء الطلب',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateOrderStatus(Request $request, $trackingCode)
    {
        $user = $request->user();
        if (! $user->hasRole('driver') || $user->role !== 'driver') {
            return response()->json([
                'message' => 'You are not authorized to update order status',
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:picked_up,delivered,returned,canceled',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // if the status is 'delivered' then set is_customer_paid to true
        $is_customer_paid = false;
        if ($request->status === 'delivered') {
            $is_customer_paid = true;
        }

        try {
            DB::beginTransaction();
            $order = Order::where('tracking_code', $trackingCode)->first();
            if ($order->status === $request->status) {
                return response()->json([
                    'message' => 'IS THE SAME!',
                ], 403);
            }
            if (! $order) {
                return response()->json([
                    'message' => 'Order not found',
                ], 404);
            }
            $order->status = $request->status;
            $order->is_customer_paid = $is_customer_paid;
            $order->save();

            $order->orderStatusLogs()->create([
                'changed_by_id' => $user->id,
                'status' => $request->status,
                'note' => 'The order status has been updated to '.$request->status.' by the driver',
            ]);
            DB::commit();

            return response()->json([
                'message' => 'Order status updated successfully',
                'order' => $order,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث حالة الطلب',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
