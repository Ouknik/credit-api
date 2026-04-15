<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('procurement_order_id');
            $table->uuid('distributor_shop_id');
            $table->enum('status', ['submitted', 'accepted', 'rejected', 'expired'])->default('submitted');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('delivery_cost', 14, 2)->default(0);
            $table->dateTime('estimated_delivery_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('procurement_order_id')->references('id')->on('procurement_orders')->cascadeOnDelete();
            $table->foreign('distributor_shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->index('procurement_order_id');
            $table->index('distributor_shop_id');
            $table->index('status');
            $table->unique(['procurement_order_id', 'distributor_shop_id'], 'proc_offers_order_dist_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_offers');
    }
};
