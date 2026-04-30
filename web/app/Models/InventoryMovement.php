<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'movement_type',
        'qty',
        'stock_before',
        'stock_after',
        'source_type',
        'source_id',
        'source_ref',
        'notes',
        'moved_by',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'source_id' => 'integer',
            'moved_by' => 'integer',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
