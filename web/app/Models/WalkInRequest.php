<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalkInRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'request_code',
        'branch_id',
        'package_id',
        'package_name',
        'package_price',
        'customer_name',
        'customer_phone',
        'add_ons_json',
        'subtotal_amount',
        'total_amount',
        'status',
        'expires_at',
        'paid_at',
        'confirmed_by',
        'transaction_id',
        'queue_ticket_id',
        'submission_key',
        'request_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'package_price' => 'decimal:2',
            'add_ons_json' => 'array',
            'subtotal_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function queueTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class);
    }
}
