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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete(); // من مَن؟
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();   // إلى مَن؟
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();    // لأي طلب؟
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['payment', 'fee', 'refund', 'cod_collection']); // نوع الحركة المالية
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
