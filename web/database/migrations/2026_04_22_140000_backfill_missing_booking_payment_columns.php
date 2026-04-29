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
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'payment_type')) {
                $table->string('payment_type', 20)->default('full');
            }

            if (! Schema::hasColumn('bookings', 'payment_gateway')) {
                $table->string('payment_gateway', 30)->nullable();
            }

            if (! Schema::hasColumn('bookings', 'payment_reference')) {
                $table->string('payment_reference', 80)->nullable();
            }

            if (! Schema::hasColumn('bookings', 'payment_token')) {
                $table->string('payment_token', 120)->nullable();
            }

            if (! Schema::hasColumn('bookings', 'payment_url')) {
                $table->text('payment_url')->nullable();
            }

            if (! Schema::hasColumn('bookings', 'payment_payload')) {
                $table->json('payment_payload')->nullable();
            }

            if (! Schema::hasColumn('bookings', 'payment_expires_at')) {
                $table->timestamp('payment_expires_at')->nullable();
            }

            if (! Schema::hasColumn('bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank to avoid dropping potentially existing production columns.
    }
};
