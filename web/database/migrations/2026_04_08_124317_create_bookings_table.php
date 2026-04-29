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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 40)->unique();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('design_catalog_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->string('customer_email')->nullable();
            $table->date('booking_date');
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            $table->string('status', 30);
            $table->string('source', 20)->default('web');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('booking_date');
            $table->index('booking_code');
            $table->index('status');
            $table->index('created_at');
            $table->index('customer_phone');
            $table->index('start_at');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist;');
            DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_no_overlap EXCLUDE USING gist (branch_id WITH =, tstzrange(start_at, end_at, '[)') WITH &&) WHERE (status IN ('pending','confirmed','paid','checked_in','in_queue','in_session'));");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_no_overlap;');
        }

        Schema::dropIfExists('bookings');
    }
};
