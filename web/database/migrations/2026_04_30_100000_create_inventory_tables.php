<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('unit', 20)->default('pcs');
            $table->unsignedInteger('available_stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('available_stock');
            $table->index('is_active');
            $table->index('sort_order');
        });

        Schema::create('add_on_inventory_items', function (Blueprint $table): void {
            $table->foreignId('add_on_id')->constrained('add_ons')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('qty_per_unit')->default(1);
            $table->timestamps();

            $table->primary(['add_on_id', 'inventory_item_id']);
        });

        Schema::create('package_inventory_items', function (Blueprint $table): void {
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('qty_per_booking')->default(1);
            $table->timestamps();

            $table->primary(['package_id', 'inventory_item_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->enum('movement_type', ['in', 'out']);
            $table->unsignedInteger('qty');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_ref', 120)->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_item_id', 'created_at']);
            $table->index(['movement_type', 'created_at']);
            $table->index(['source_type', 'source_id']);
            $table->unique(
                ['inventory_item_id', 'source_type', 'source_id', 'movement_type'],
                'inventory_movements_source_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('package_inventory_items');
        Schema::dropIfExists('add_on_inventory_items');
        Schema::dropIfExists('inventory_items');
    }
};
