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
            if (! Schema::hasColumn('bookings', 'transfer_proof_path')) {
                $table->string('transfer_proof_path', 255)->nullable()->after('payment_payload');
            }

            if (! Schema::hasColumn('bookings', 'transfer_proof_uploaded_at')) {
                $table->timestamp('transfer_proof_uploaded_at')->nullable()->after('transfer_proof_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'transfer_proof_uploaded_at')) {
                $table->dropColumn('transfer_proof_uploaded_at');
            }

            if (Schema::hasColumn('bookings', 'transfer_proof_path')) {
                $table->dropColumn('transfer_proof_path');
            }
        });
    }
};
