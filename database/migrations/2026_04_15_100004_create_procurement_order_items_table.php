<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('procurement_order_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 14, 3);
            $table->string('unit', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('procurement_order_id')->references('id')->on('procurement_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->index('procurement_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_order_items');
    }
};
