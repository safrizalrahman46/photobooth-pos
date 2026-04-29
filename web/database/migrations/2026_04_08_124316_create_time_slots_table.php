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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_bookable')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'slot_date', 'start_time', 'end_time'], 'time_slots_unique_range');
            $table->index(['slot_date', 'is_bookable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
