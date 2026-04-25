<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('add_ons', function (Blueprint $table) {
            $table->unsignedInteger('available_stock')->default(0)->after('is_physical');
            $table->unsignedInteger('low_stock_threshold')->default(0)->after('available_stock');
            $table->index('available_stock');
        });

        DB::table('add_ons')
            ->where('is_physical', true)
            ->update([
                'available_stock' => 20,
                'low_stock_threshold' => 5,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('add_ons', function (Blueprint $table) {
            $table->dropIndex(['available_stock']);
            $table->dropColumn(['available_stock', 'low_stock_threshold']);
        });
    }
};
