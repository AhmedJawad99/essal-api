<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            
            $table->string('tracking_code')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_phone_alt')->nullable();
            
            $table->enum('type', ['pickup', 'delivery'])->default('delivery');
            $table->string('status')->default('pending'); // pending, processing, with_driver, delivered, returned
            $table->enum('payment_method', ['cod', 'online'])->default('cod');
            $table->boolean('is_customer_paid')->default(false);
            
            $table->string('pickup_address');
            $table->string('pickup_gps_link')->nullable();
            $table->string('delivery_address');
            $table->string('delivery_gps_link')->nullable();
            
            $table->decimal('delivery_cost', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
