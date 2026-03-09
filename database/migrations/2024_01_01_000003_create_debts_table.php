<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shop_id');
            $table->uuid('customer_id');
            $table->decimal('amount', 14, 2);
            $table->enum('type', ['manual', 'recharge', 'payment'])->default('manual');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->index('shop_id');
            $table->index('customer_id');
            $table->index(['shop_id', 'customer_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
