<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_offer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('procurement_offer_id');
            $table->uuid('procurement_order_item_id');
            $table->uuid('product_id');
            $table->boolean('is_available')->default(true);
            $table->decimal('unit_price', 14, 2)->nullable();
            $table->decimal('quantity', 14, 3)->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('procurement_offer_id')->references('id')->on('procurement_offers')->cascadeOnDelete();
            $table->foreign('procurement_order_item_id')->references('id')->on('procurement_order_items')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->index('procurement_offer_id');
            $table->index('procurement_order_item_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_offer_items');
    }
};
