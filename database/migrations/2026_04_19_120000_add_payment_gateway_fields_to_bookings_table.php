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
            $table->string('payment_type', 20)->default('onsite')->after('source');
            $table->string('payment_gateway', 30)->nullable()->after('payment_type');
            $table->string('payment_reference', 80)->nullable()->after('payment_gateway');
            $table->string('payment_token', 120)->nullable()->after('payment_reference');
            $table->text('payment_url')->nullable()->after('payment_token');
            $table->json('payment_payload')->nullable()->after('payment_url');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_payload');
            $table->timestamp('paid_at')->nullable()->after('payment_expires_at');

            $table->index('payment_reference');
            $table->index('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['payment_reference']);
            $table->dropIndex(['payment_type']);
            $table->dropColumn([
                'payment_type',
                'payment_gateway',
                'payment_reference',
                'payment_token',
                'payment_url',
                'payment_payload',
                'payment_expires_at',
                'paid_at',
            ]);
        });
    }
};
