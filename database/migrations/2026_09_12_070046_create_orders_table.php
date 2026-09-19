<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // المفاتيح الأجنبية
            $table->foreignId('merchant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('region_id')->constrained('regions')->onDelete('restrict');

            // بيانات الطلب الأساسية
            $table->string('tracking_code')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_phone_alt')->nullable();

            $table->text('order_description');

            $table->string('type'); // pickup, delivery
            $table->string('status')->default('pending'); // pending, accepted, picked_up, delivered, returned
            $table->string('payment_method');
            $table->boolean('is_customer_paid')->default(false);

            // العناوين
            $table->string('pickup_address')->nullable();
            $table->string('pickup_gps_link')->nullable();
            $table->string('delivery_address');
            $table->string('delivery_gps_link')->nullable();

            // المالية
            $table->decimal('delivery_cost', 10, 2);
            $table->decimal('total_amount', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
