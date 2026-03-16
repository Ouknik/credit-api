<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First truncate long phone values while column is still varchar(255)
        \DB::table('shops')->update([
            'phone' => \DB::raw("LEFT(TRIM(COALESCE(phone, '0000000000')), 20)"),
        ]);
        \DB::table('shops')->where('phone', '')->update(['phone' => '0000000000']);

        // Alter column in two steps to avoid strict-mode truncation errors
        \DB::statement("ALTER TABLE `shops` MODIFY `phone` VARCHAR(20) NULL");
        \DB::statement("ALTER TABLE `shops` MODIFY `phone` VARCHAR(20) NOT NULL");

        // Drop email unique index if it exists (name may vary)
        $emailIndexes = \DB::select("SHOW INDEX FROM `shops` WHERE Column_name = 'email' AND Non_unique = 0");
        foreach ($emailIndexes as $idx) {
            \DB::statement("ALTER TABLE `shops` DROP INDEX `{$idx->Key_name}`");
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
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
