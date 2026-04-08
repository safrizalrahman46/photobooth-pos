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
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('queue_code', 40)->unique();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->date('queue_date');
            $table->unsignedInteger('queue_number');
            $table->string('source_type', 20);
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 30)->nullable();
            $table->string('status', 30);
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamp('called_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'queue_date', 'queue_number']);
            $table->index(['queue_date', 'status']);
            $table->index('booking_id');
            $table->index('created_at');
            $table->index('customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
