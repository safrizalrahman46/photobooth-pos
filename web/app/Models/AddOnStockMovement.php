<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddOnStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'add_on_id',
        'movement_type',
        'qty',
        'stock_before',
        'stock_after',
        'notes',
        'moved_by',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'moved_by' => 'integer',
        ];
    }

    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
