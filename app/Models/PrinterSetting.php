<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrinterSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'device_name',
        'printer_type',
        'connection',
        'paper_width_mm',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'connection' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
