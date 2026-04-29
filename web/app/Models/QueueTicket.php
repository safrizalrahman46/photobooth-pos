<?php

namespace App\Models;

use App\Enums\QueueSourceType;
use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QueueTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_code',
        'branch_id',
        'queue_date',
        'queue_number',
        'source_type',
        'booking_id',
        'customer_name',
        'customer_phone',
        'status',
        'priority',
        'called_at',
        'checked_in_at',
        'started_at',
        'finished_at',
        'skipped_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
            'source_type' => QueueSourceType::class,
            'status' => QueueStatus::class,
            'called_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'skipped_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
