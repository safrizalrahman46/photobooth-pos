<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['branch_id', 'booking_date'], 'bookings_branch_date_idx');
            $table->index(['package_id', 'booking_date'], 'bookings_package_date_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['branch_id', 'created_at'], 'transactions_branch_created_idx');
            $table->index(['cashier_id', 'created_at'], 'transactions_cashier_created_idx');
        });

        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->index(['branch_id', 'queue_date'], 'queue_tickets_branch_date_idx');
            $table->index(['status', 'queue_date'], 'queue_tickets_status_date_idx');
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->index(['item_type', 'item_ref_id'], 'transaction_items_type_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropIndex('transaction_items_type_ref_idx');
        });

        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->dropIndex('queue_tickets_branch_date_idx');
            $table->dropIndex('queue_tickets_status_date_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_branch_created_idx');
            $table->dropIndex('transactions_cashier_created_idx');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_branch_date_idx');
            $table->dropIndex('bookings_package_date_idx');
        });
    }
};
