<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AddOn extends Model
{
    use HasFactory;

    protected $table = 'add_ons';

    protected $fillable = [
        'package_id',
        'code',
        'name',
        'description',
        'price',
        'max_qty',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_qty' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_add_ons')
            ->withPivot(['qty', 'unit_price', 'line_total'])
            ->withTimestamps();
    }
}
