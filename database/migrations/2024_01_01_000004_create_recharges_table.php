<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shop_id');
            $table->uuid('customer_id')->nullable();
            $table->string('phone');
            $table->string('operator');
            $table->decimal('amount', 14, 2);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('reference_code')->unique();
            $table->string('idempotency_key')->unique()->nullable();
            $table->timestamps();
            
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->index('shop_id');
            $table->index('customer_id');
            $table->index('phone');
            $table->index('status');
            $table->index('reference_code');
            $table->index(['shop_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharges');
    }
};
