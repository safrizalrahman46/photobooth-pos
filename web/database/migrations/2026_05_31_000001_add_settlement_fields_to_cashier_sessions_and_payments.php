<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('cashier_sessions', 'business_date')) {
                $table->date('business_date')->nullable()->after('branch_id');
            }

            if (! Schema::hasColumn('cashier_sessions', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('cashier_sessions', 'is_late_close')) {
                $table->boolean('is_late_close')->default(false)->after('closing_cash');
            }
        });

        Schema::table('cashier_sessions', function (Blueprint $table): void {
            $table->index('business_date');
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'cashier_session_id')) {
                $table->foreignId('cashier_session_id')->nullable()->after('transaction_id')->constrained('cashier_sessions')->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'payment_stage')) {
                $table->string('payment_stage', 30)->default('full')->after('method');
            }

            if (! Schema::hasColumn('payments', 'net_amount')) {
                $table->decimal('net_amount', 12, 2)->nullable()->after('amount');
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index('payment_stage');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_payment_stage_index');
            if (Schema::hasColumn('payments', 'cashier_session_id')) {
                $table->dropConstrainedForeignId('cashier_session_id');
            }

            foreach (['payment_stage', 'net_amount'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('cashier_sessions', function (Blueprint $table): void {
            $table->dropIndex('cashier_sessions_business_date_index');
            if (Schema::hasColumn('cashier_sessions', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }

            foreach (['business_date', 'is_late_close'] as $column) {
                if (Schema::hasColumn('cashier_sessions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

};
