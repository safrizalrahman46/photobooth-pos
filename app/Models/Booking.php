<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'branch_id',
        'package_id',
        'design_catalog_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'booking_date',
        'start_at',
        'end_at',
        'status',
        'source',
        'payment_type',
        'payment_gateway',
        'payment_reference',
        'payment_token',
        'payment_url',
        'payment_payload',
        'transfer_proof_path',
        'transfer_proof_uploaded_at',
        'addons',
        'addon_total',
        'payment_expires_at',
        'paid_at',
        'total_amount',
        'deposit_amount',
        'paid_amount',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'approved_at' => 'datetime',
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'payment_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'transfer_proof_uploaded_at' => 'datetime',
            'payment_payload' => 'array',
            'addons' => 'array',
            'addon_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
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

    public function designCatalog(): BelongsTo
    {
        return $this->belongsTo(DesignCatalog::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(BookingStatusLog::class);
    }

    public function queueTicket(): HasOne
    {
        return $this->hasOne(QueueTicket::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'booking_add_ons')
            ->withPivot(['qty', 'unit_price', 'line_total'])
            ->withTimestamps();
    }
}
