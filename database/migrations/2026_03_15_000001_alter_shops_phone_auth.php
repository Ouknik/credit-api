<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropUnique(['email']);

            $table->string('email')->nullable()->change();

            $table->string('phone', 20)->nullable(false)->change();
            $table->unique('phone');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['shops_phone_index']);

            $table->string('phone')->nullable()->change();
            $table->string('email')->nullable(false)->change();
            $table->unique('email');
        });
    }
};
