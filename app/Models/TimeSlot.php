<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'slot_date',
        'start_time',
        'end_time',
        'capacity',
        'is_bookable',
    ];

    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
            'is_bookable' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
