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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2); // إجمالي المبلغ المسدد للتاجر
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('transfer_receipt')->nullable(); // رابط أو مسار صورة وصل التحويل البنكي
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
