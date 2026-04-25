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
            $table->boolean('is_physical')->default(false)->after('max_qty');
            $table->index('is_physical');
        });

        DB::table('add_ons')
            ->whereIn('code', [
                'AON-COSTUME',
                'AON-KEYCHAIN-ACRYLIC',
                'AON-KEYCHAIN-METAL',
                'AON-DIY-CARD',
                'AON-PARTY-PROP-SET',
                'AON-PREMIUM-EXTENDED-LIGHTING',
            ])
            ->update(['is_physical' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('add_ons', function (Blueprint $table) {
            $table->dropIndex(['is_physical']);
            $table->dropColumn('is_physical');
        });
    }
};
