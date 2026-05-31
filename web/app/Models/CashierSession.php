<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashierSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'business_date',
        'opened_at',
        'closed_at',
        'closed_by',
        'opening_cash',
        'closing_cash',
        'is_late_close',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'is_late_close' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function cashExpenses(): HasMany
    {
        return $this->hasMany(CashExpense::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(CashierSettlement::class);
    }
}
