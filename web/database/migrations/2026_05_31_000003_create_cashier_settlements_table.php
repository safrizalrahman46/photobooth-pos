<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_settlements', function (Blueprint $table): void {
            $table->id();
            $table->string('settlement_code', 40)->unique();
            $table->foreignId('cashier_session_id')->unique()->constrained('cashier_sessions')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->date('business_date');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at');
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('cash_received', 12, 2)->default(0);
            $table->decimal('non_cash_received', 12, 2)->default(0);
            $table->decimal('qris_received', 12, 2)->default(0);
            $table->decimal('transfer_received', 12, 2)->default(0);
            $table->decimal('card_received', 12, 2)->default(0);
            $table->decimal('cash_expenses_total', 12, 2)->default(0);
            $table->decimal('cash_to_deposit', 12, 2)->default(0);
            $table->decimal('owner_received_cash', 12, 2)->nullable();
            $table->decimal('discrepancy_amount', 12, 2)->default(0);
            $table->decimal('corrections_total', 12, 2)->default(0);
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamp('first_printed_at')->nullable();
            $table->timestamp('last_printed_at')->nullable();
            $table->boolean('is_late_close')->default(false);
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_date', 'branch_id']);
            $table->index(['cashier_id', 'business_date']);
            $table->index('closed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_settlements');
    }
};
