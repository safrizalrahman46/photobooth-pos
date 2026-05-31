<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_code',
        'cashier_session_id',
        'branch_id',
        'cashier_id',
        'business_date',
        'opened_at',
        'closed_at',
        'opening_cash',
        'total_sales',
        'cash_received',
        'non_cash_received',
        'qris_received',
        'transfer_received',
        'card_received',
        'cash_expenses_total',
        'cash_to_deposit',
        'owner_received_cash',
        'discrepancy_amount',
        'corrections_total',
        'print_count',
        'first_printed_at',
        'last_printed_at',
        'is_late_close',
        'snapshot',
        'created_by',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'cash_received' => 'decimal:2',
            'non_cash_received' => 'decimal:2',
            'qris_received' => 'decimal:2',
            'transfer_received' => 'decimal:2',
            'card_received' => 'decimal:2',
            'cash_expenses_total' => 'decimal:2',
            'cash_to_deposit' => 'decimal:2',
            'owner_received_cash' => 'decimal:2',
            'discrepancy_amount' => 'decimal:2',
            'corrections_total' => 'decimal:2',
            'first_printed_at' => 'datetime',
            'last_printed_at' => 'datetime',
            'is_late_close' => 'boolean',
            'snapshot' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function cashierSession(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(CashierSettlementCorrection::class);
    }
}
