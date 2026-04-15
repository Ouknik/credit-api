<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            $table->uuid('shop_id');
            $table->enum('status', [
                'draft',
                'published',
                'receiving_offers',
                'accepted',
                'preparing',
                'on_delivery',
                'delivered',
                'cancelled',
            ])->default('draft');
            $table->string('delivery_address')->nullable();
            $table->decimal('delivery_lat', 10, 7)->nullable();
            $table->decimal('delivery_lng', 10, 7)->nullable();
            $table->dateTime('preferred_delivery_time')->nullable();
            $table->text('notes')->nullable();
            $table->string('confirmation_pin', 10)->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->index('shop_id');
            $table->index('status');
            $table->index(['shop_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_orders');
    }
};
