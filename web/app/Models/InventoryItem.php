<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'available_stock',
        'low_stock_threshold',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'available_stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'add_on_inventory_items')
            ->withPivot(['qty_per_unit'])
            ->withTimestamps();
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_inventory_items')
            ->withPivot(['qty_per_booking'])
            ->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
