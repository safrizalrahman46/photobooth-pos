<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_on_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('add_on_id')->constrained('add_ons')->cascadeOnDelete();
            $table->enum('movement_type', ['in', 'out']);
            $table->unsignedInteger('qty');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('notes', 500)->nullable();
            $table->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['add_on_id', 'created_at']);
            $table->index(['movement_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_on_stock_movements');
    }
};
