<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('source_name', 120);
            $table->string('source_type', 30)->default('other');
            $table->text('description')->nullable();
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'valid_from', 'valid_until']);
            $table->index(['branch_id', 'package_id']);
            $table->index(['source_type', 'source_name']);
        });

        Schema::create('referral_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('referral_code_id')->nullable()->constrained('referral_codes')->nullOnDelete();
            $table->string('referral_code', 40);
            $table->string('source_name', 120)->nullable();
            $table->string('source_type', 30)->nullable();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('queue_ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name', 120)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('channel', 30)->default('unknown');
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('applied');
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at');
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['referral_code_id', 'status']);
            $table->index(['referral_code', 'redeemed_at']);
            $table->index(['channel', 'redeemed_at']);
            $table->index(['branch_id', 'redeemed_at']);
            $table->index(['package_id', 'redeemed_at']);
            $table->index(['booking_id', 'status']);
            $table->index(['transaction_id', 'status']);
            $table->index('customer_phone');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 12, 2)->default(0)->after('addon_total');
            }

            if (! Schema::hasColumn('bookings', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal_amount');
            }

            if (! Schema::hasColumn('bookings', 'referral_code_id')) {
                $table->foreignId('referral_code_id')->nullable()->after('discount_amount')->constrained('referral_codes')->nullOnDelete();
            }

            if (! Schema::hasColumn('bookings', 'referral_code')) {
                $table->string('referral_code', 40)->nullable()->after('referral_code_id');
            }

            if (! Schema::hasColumn('bookings', 'referral_discount_amount')) {
                $table->decimal('referral_discount_amount', 12, 2)->default(0)->after('referral_code');
            }
        });

        Schema::table('transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('transactions', 'referral_code_id')) {
                $table->foreignId('referral_code_id')->nullable()->after('discount_amount')->constrained('referral_codes')->nullOnDelete();
            }

            if (! Schema::hasColumn('transactions', 'referral_code')) {
                $table->string('referral_code', 40)->nullable()->after('referral_code_id');
            }

            if (! Schema::hasColumn('transactions', 'referral_discount_amount')) {
                $table->decimal('referral_discount_amount', 12, 2)->default(0)->after('referral_code');
            }
        });

        DB::table('bookings')
            ->where(function ($query): void {
                $query->whereNull('subtotal_amount')->orWhere('subtotal_amount', 0);
            })
            ->update([
                'subtotal_amount' => DB::raw('total_amount'),
                'discount_amount' => DB::raw('COALESCE(discount_amount, 0)'),
                'referral_discount_amount' => DB::raw('COALESCE(referral_discount_amount, 0)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('transactions', 'referral_code_id')) {
                $table->dropConstrainedForeignId('referral_code_id');
            }

            if (Schema::hasColumn('transactions', 'referral_code')) {
                $table->dropColumn('referral_code');
            }

            if (Schema::hasColumn('transactions', 'referral_discount_amount')) {
                $table->dropColumn('referral_discount_amount');
            }
        });

        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'referral_code_id')) {
                $table->dropConstrainedForeignId('referral_code_id');
            }

            foreach (['subtotal_amount', 'discount_amount', 'referral_code', 'referral_discount_amount'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('referral_redemptions');
        Schema::dropIfExists('referral_codes');
    }
};
