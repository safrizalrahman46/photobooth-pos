<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierSettlementCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_settlement_id',
        'created_by',
        'amount',
        'affects_cash',
        'reason',
        'snapshot_before',
        'snapshot_after',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'affects_cash' => 'boolean',
            'snapshot_before' => 'array',
            'snapshot_after' => 'array',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CashierSettlement::class, 'cashier_settlement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
