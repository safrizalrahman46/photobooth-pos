<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralRedemption extends Model
{
    use HasFactory;

    public const STATUS_APPLIED = 'applied';

    public const STATUS_PAID = 'paid';

    public const STATUS_DONE = 'done';

    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'referral_code_id',
        'referral_code',
        'source_name',
        'source_type',
        'booking_id',
        'transaction_id',
        'queue_ticket_id',
        'branch_id',
        'package_id',
        'customer_name',
        'customer_phone',
        'channel',
        'subtotal_amount',
        'discount_amount',
        'final_amount',
        'status',
        'applied_by',
        'redeemed_at',
        'voided_at',
        'voided_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'redeemed_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function queueTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
