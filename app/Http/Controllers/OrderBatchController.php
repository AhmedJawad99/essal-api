<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderBatchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('admin') && $user->role !== 'admin') {
            return response()->json([
                'message' => 'You are not authorized to view order batches',
            ], 403);
        }
        $orderBatches = OrderBatch::where('region_id', $user->adminProfile->region_id)->get();

        if ($orderBatches->isEmpty()) {
            return response()->json([
                'message' => 'No order batches found',
            ], 404);
        }

        return response()->json([
            'order_batches' => $orderBatches,
        ]);
    }

    public function store(Request $request, $batchCode = null)
    {
        $user = $request->user();
        if (! $user->hasRole('admin') && $user->role !== 'admin') {
            return response()->json([
                'message' => 'You are not authorized to create an order batch',
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => [
                'required',
                'exists:orders,id',
                function ($attribute, $value, $fail) use ($request) {
                    $order = Order::find($value);
                    if ($order->region_id != $request->user()->adminProfile->region_id) {
                        $fail("The order with ID {$value} does not belong to the specified region.");
                    }
                    if ($order->batch_id !== null) {
                        $fail("The order with ID {$value} is already assigned to a batch.");
                    }
                    if ($order->status !== 'pending') {
                        $fail("The order with ID {$value} is not in a pending status.");
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $orderBatch = null;
            // If batchCode is not provided, generate a new one
            if (! $batchCode) {
                $batchCode = 'BATCH-'.strtoupper(Str::random(8));
                $orderBatch = OrderBatch::create([
                    'region_id' => $request->user()->adminProfile->region_id,
                    'batch_code' => $batchCode,
                    'status' => 'open',
                ]);
            }
            // If batchCode is provided, check if it exists or not
            if ($batchCode) {
                $orderBatch = OrderBatch::where('batch_code', $batchCode)->first();
                if (! $orderBatch) {
                    return response()->json([
                        'message' => 'Order batch not found',
                    ], 404);
                }
            }

            if ($orderBatch->status !== 'open') {
                return response()->json([
                    'message' => 'Cannot add orders to a batch that is not open',
                ], 400);
            }

            if ($orderBatch->region_id != $request->user()->adminProfile->region_id) {
                return response()->json([
                    'message' => 'Cannot add orders to a batch that belongs to a different region',
                ], 400);
            }

            Order::whereIn('id', $request->order_ids)->update(['batch_id' => $orderBatch->id]);
            DB::commit();

            return response()->json([
                'message' => 'Order batch created successfully',
                'batch_code' => $batchCode,
                'total_orders' => count($request->order_ids),
                'batch' => $orderBatch->load('orders'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create order batch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showBatchCode(Request $request, $batchCode)
    {
        $user = $request->user();
        if (! $user->hasRole('admin') && ! $user->hasRole('driver')) {
            return response()->json([
                'message' => 'You are not authorized to view order batches',
            ], 403);
        }

        $orderBatch = OrderBatch::where('batch_code', $batchCode)->with('orders')->first();

        if (! $orderBatch) {
            return response()->json([
                'message' => 'Order batch not found',
            ], 404);
        }

        $orders = $orderBatch->orders()->get();

        return response()->json([
            'batch_code' => $orderBatch->batch_code,
            'status' => $orderBatch->status,
            'region_id' => $orderBatch->region_id,
            'driver_id' => $orderBatch->driver_id,
            'orders' => $orders,
        ]);
    }

    public function removeOrdersFromBatch(Request $request, $batchCode)
    {
        $user = $request->user();
        if (! $user->hasRole('admin')) {
            return response()->json([
                'message' => 'You are not authorized to remove orders from a batch',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => [
                'required',
                'exists:orders,id',
                function ($attribute, $value, $fail) use ($request) {
                    $order = Order::find($value);
                    if ($order->region_id != $request->user()->adminProfile->region_id) {
                        $fail("The order with ID {$value} does not belong to the specified region.");
                    }
                    if ($order->batch_id === null) {
                        $fail("The order with ID {$value} is not assigned to any batch.");
                    }
                    if ($order->status !== 'pending') {
                        $fail("The order with ID {$value} is not in a pending status.");
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $orderBatch = OrderBatch::where('batch_code', $batchCode)->first();
        if (! $orderBatch) {
            return response()->json([
                'message' => 'Order batch not found',
            ], 404);
        }

        if ($orderBatch->region_id != $request->user()->adminProfile->region_id) {
            return response()->json([
                'message' => 'Cannot remove orders from a batch that belongs to a different region',
            ], 400);
        }

        if ($orderBatch->status !== 'open') {
            return response()->json([
                'message' => 'Cannot remove orders from a batch that is not open',
            ], 400);
        }

        try {
            DB::beginTransaction();
            Order::whereIn('id', $request->order_ids)->update(['batch_id' => null]);
            DB::commit();

            $orderBatch->load('orders');

            return response()->json([
                'message' => 'Orders removed from batch successfully',
                'batch_code' => $batchCode,
                'total_orders' => $orderBatch->orders()->count(),
                'batch' => $orderBatch,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to remove orders from batch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function removeBatch(Request $request, $batchCode)
    {
        $orderBatch = OrderBatch::where('batch_code', $batchCode)->first();
        if (! $orderBatch) {
            return response()->json([
                'message' => 'Order batch not found',
            ], 404);
        }

        // check if the batch is open
        if ($orderBatch->status !== 'open') {
            return response()->json([
                'message' => 'Cannot remove a batch that is not open',
            ], 400);
        }

        // check if the batch belongs to the authenticated admin
        if ($orderBatch->region_id != $request->user()->adminProfile->region_id) {
            return response()->json([
                'message' => 'Cannot remove a batch that belongs to a different region',
            ], 400);
        }

        try {
            DB::beginTransaction();
            Order::where('batch_id', $orderBatch->id)->update(['batch_id' => null]);
            $orderBatch->delete();
            DB::commit();

            return response()->json([
                'message' => 'Order batch removed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to remove order batch',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    // driver
    public function assignDriverToBatch(Request $request, $batchCode)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin') || $user->role === 'admin';
        $isDriver = $user->hasRole('driver') || $user->role === 'driver';

        // 1. التحقق من الصلاحيات (أدمن أو مندوب فقط)
        if (! $isAdmin && ! $isDriver) {
            return response()->json([
                'message' => 'You are not authorized to assign a batch to a driver',
            ], 403);
        }

        // 2. إذا كان أدمن، يجب أن يُرسل ID المندوب
        if ($isAdmin) {
            $validator = Validator::make($request->all(), [
                'driver_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        }

        // 3. البحث عن السلة
        $orderBatch = OrderBatch::where('batch_code', $batchCode)->first();
        if (! $orderBatch) {
            return response()->json(['message' => 'Order batch not found'], 404);
        }

        // 4. التحقق من حالة السلة
        if ($orderBatch->status !== 'open') {
            return response()->json(['message' => 'Cannot assign a batch that is not open'], 400);
        }

        // 5. إذا كان المستخدم أدمن، نتحقق أن السلة في منطقته
        if ($isAdmin && $orderBatch->region_id != $user->adminProfile->region_id) {
            return response()->json(['message' => 'Cannot assign a batch that belongs to a different region'], 400);
        }

        // 6. تحديد من هو المندوب الذي سيستلم السلة
        // إذا كان المستخدم مندوباً، يستلمها هو. إذا كان أدمن، نعطيها للمندوب المُرسل في الطلب.
        $driverId = $isDriver ? $user->id : $request->driver_id;

        try {
            DB::beginTransaction();

            // 7. تحديث حالة السلة وربطها بالمندوب
            $orderBatch->update([
                'driver_id' => $driverId,
                'status' => 'assigned', // أو 'picked_up' حسب سير العمل لديك
            ]);

            // 8. تحديث جميع الطلبات داخل هذه السلة
            Order::where('batch_id', $orderBatch->id)->update([
                'driver_id' => $driverId,
                'status' => 'picked_up', // عادة الطلب يعتبر قيد التوصيل بمجرد تعيينه لمندوب
            ]);

            // 9. (اختياري) إضافة Logs للطلبات
            $orders = $orderBatch->orders;
            foreach ($orders as $order) {
                $order->orderStatusLogs()->create([
                    'changed_by_id' => $user->id,
                    'status' => 'picked_up',
                    'note' => $isDriver ? 'المندوب استلم السلة' : 'تم تعيين السلة للمندوب بواسطة الإدارة',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Batch assigned to driver successfully',
                'batch_code' => $batchCode,
                'driver_id' => $driverId,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to assign batch to driver',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function pickingUpOrders(Request $request, $batchCode)
    {
        $user = $request->user();

        if (! $user->hasRole('driver') && $user->role !== 'driver') {
            return response()->json([
                'message' => 'You are not authorized to pick up orders from a batch',
            ], 403);
        }

        // 1. جلب الدفعة أولاً لتسهيل المقارنة والتحقق
        $orderBatch = OrderBatch::where('batch_code', $batchCode)->first();
        $ordersCount = Order::where('batch_id', $orderBatch->id)->get()->count();
        if (! $orderBatch) {
            return response()->json([
                'message' => 'Order batch not found',
            ], 404);
        }

        if ($orderBatch->status === 'picked_up') {
            return response()->json([
                'message' => 'Order batch is already picked up',
                'batch_code' => $orderBatch->batch_code,
                'driver_id' => $orderBatch->driver_id,
            ], 400);
        }

        if ($orderBatch->status !== 'assigned') {
            return response()->json([
                'message' => 'Cannot pick up orders from a batch that is not assigned',
            ], 400);
        }

        if ($orderBatch->driver_id !== $user->id) {
            return response()->json([
                'message' => 'Cannot pick up orders from a batch that is not assigned to you',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => [
                'required',
                'exists:orders,id',
                // 2. تمرير المتغيرات المطلوبة عبر use
                function ($attribute, $value, $fail) use ($user, $orderBatch) {
                    $order = Order::find($value);

                    // 3. تأكد من اسم العلاقة الصحيحة للمندوب (استبدل adminProfile بـ driverProfile أو العلاقة الصحيحة لديك)
                    $userRegionId = $user->driverProfile->region_id ?? $user->region_id;

                    if ($order->region_id != $userRegionId) {
                        $fail("The order with ID {$value} does not belong to the specified region.");
                    }

                    // 4. مقارنة id الدفعة بشكل منطقي وسليم بدلاً من كود الدفعة
                    if ($order->batch_id !== $orderBatch->id) {
                        $fail("The order with ID {$value} is not in the same batch.");
                    }

                    if ($order->driver_id !== $user->id) {
                        $fail("The order with ID {$value} is not assigned to you.");
                    }

                    if ($order->status !== 'picked_up') {
                        $fail("The order with ID {$value} not picked_up.");
                    }
                },
            ],
        ]);

        if ($ordersCount !== count($request->order_ids)) {
            return response()->json([
                'message' => 'You must pick up all orders from this batch',
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $orderBatch->update([
                'status' => 'picked_up',
            ]);

            $orders = $orderBatch->orders;

            DB::commit();

            return response()->json([
                'message' => 'Batch picked up successfully',
                'batch_code' => $batchCode,
                'driver_id' => $user->id,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to pick up orders from batch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function batchCompleted(Request $request, $batchCode)
    {
        $user = $request->user();
        if (! $user->hasRole('driver') || ! $user->role === 'driver') {
            return response()->json([
                'message' => 'You are not authorized to complete this batch',
            ], 403);
        }
        $orderBatch = OrderBatch::where('batch_code', $batchCode)->first();
        if (! $orderBatch) {
            return response()->json([
                'message' => 'Order batch not found',
            ], 404);
        }
        if (! $orderBatch->driver_id === $user->id) {
            return response()->json([
                'message' => 'You are not authorized to complete this batch',
            ], 403);
        }
        if ($orderBatch->status !== 'picked_up') {
            return response()->json([
                'message' => 'Cannot complete a batch that is not picked up',
            ], 400);
        }
        $orders = Order::where('batch_id', $orderBatch->id)->get();
        $completedCount = 0;
        foreach ($orders as $i) {
            if ($i->status === 'pending' || $i->status === 'picked_up') {
                $completedCount++;
            }
        }
        if ($completedCount !== 0) {
            return response()->json([
                'message' => 'Cannot complete a batch that is not completed',
            ], 400);
        }

        try {
            DB::beginTransaction();

            OrderBatch::where('batch_code', $batchCode)->update([
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Batch completed successfully',
                'batch_code' => $batchCode,
                'driver_id' => $user->id,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to complete batch',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}
