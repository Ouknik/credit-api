<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shop_id');
            $table->string('name');
            $table->string('phone');
            $table->text('address')->nullable();
            $table->decimal('total_debt', 14, 2)->default(0);
            $table->boolean('is_trusted')->default(false);
            $table->decimal('daily_limit', 14, 2)->nullable();
            $table->decimal('monthly_limit', 14, 2)->nullable();
            $table->decimal('max_debt_limit', 14, 2)->nullable();
            $table->timestamps();
            
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->index('shop_id');
            $table->index('phone');
            $table->index(['shop_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
