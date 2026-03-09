<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recharges', function (Blueprint $table) {
            // Offer code sent to the Raspberry Pi gateway
            $table->string('offer')->nullable()->after('amount');

            // Raw JSON response from the gateway
            $table->json('gateway_response')->nullable()->after('idempotency_key');

            // Expand status enum to include processing & balance_error
            // We use string instead of enum for flexibility
        });

        // Change status column from enum to varchar to support new values
        // (processing, balance_error in addition to pending, success, failed)
        Schema::table('recharges', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('recharges', function (Blueprint $table) {
            $table->dropColumn(['offer', 'gateway_response']);
        });
    }
};
