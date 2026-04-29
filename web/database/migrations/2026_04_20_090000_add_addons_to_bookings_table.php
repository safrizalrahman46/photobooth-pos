<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->json('addons')->nullable()->after('payment_payload');
            $table->decimal('addon_total', 12, 2)->default(0)->after('addons');

            $table->index('addon_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['addon_total']);
            $table->dropColumn(['addons', 'addon_total']);
        });
    }
};
