<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. التحقق من صحة البيانات (Validation)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:merchant,driver',

            // بيانات اختيارية إذا كان تاجراً
            'store_name' => 'required_if:role,merchant|string|max:255',

            // بيانات اختيارية إذا كان مندوباً
            'vehicle_type' => 'required_if:role,driver|string|max:255',
            'plate_number' => 'required_if:role,driver|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction(); // بدء معاملة قاعدة البيانات

            // 2. إنشاء المستخدم
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => $request->role,
            ]);

            // 3. إعطاء الصلاحية (Spatie)
            $user->assignRole($request->role);

            // 4. إنشاء الملف الشخصي حسب النوع (بالطريقة الصحيحة عبر العلاقات)
            if ($request->role === 'merchant') {
                $user->merchantProfile()->create([
                    'store_name' => $request->store_name,
                    'store_address' => $request->store_address ?? null,
                    'gps_link' => $request->gps_link ?? null,
                ]);
            } elseif ($request->role === 'driver') {
                $user->driverProfile()->create([
                    'vehicle_type' => $request->vehicle_type,
                    'plate_number' => $request->plate_number,
                    'wallet_balance' => 0, // رصيد افتراضي
                    'current_lat' => $request->current_lat ?? null,
                    'current_lng' => $request->current_lng ?? null,

                ]);
            }

            // 5. إنشاء التوكن (Sanctum)
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit(); // حفظ كل البيانات بنجاح

            // 6. إرجاع الاستجابة للتطبيق
            return response()->json([
                'message' => 'تم التسجيل بنجاح',
                'user' => new UserResource($user->load(['merchantProfile', 'driverProfile'])), // تحميل العلاقات
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);
        } catch (\Exception $e) { // تصحيح الخطأ الإملائي هنا
            DB::rollBack(); // التراجع عن كل شيء

            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => new UserResource($user->load(['merchantProfile', 'driverProfile', 'adminProfile'])), // تحميل العلاقات
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(new UserResource($request->user()->load(['merchantProfile', 'driverProfile', 'adminProfile'])));
    }
}
