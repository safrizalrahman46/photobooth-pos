<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'description',
        'sample_photos',
        'duration_minutes',
        'base_price',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sample_photos' => 'array',
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function designCatalogs(): HasMany
    {
        return $this->hasMany(DesignCatalog::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function addOns(): HasMany
    {
        return $this->hasMany(AddOn::class);
    }

    public function resolvedSamplePhotos(): array
    {
        return self::resolveSamplePhotoUrls($this->sample_photos ?? []);
    }

    public static function resolveSamplePhotoUrls(array|string|null $raw): array
    {
        $rows = is_array($raw) ? $raw : preg_split('/\r\n|\r|\n/', (string) $raw);

        return collect($rows)
            ->map(fn ($item): ?string => self::resolveSamplePhotoUrl($item))
            ->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
            ->unique()
            ->values()
            ->take(12)
            ->all();
    }

    public static function resolveSamplePhotoUrl(mixed $raw): ?string
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $value) === 1) {
            $absolutePath = (string) (parse_url($value, PHP_URL_PATH) ?? '');

            if ($absolutePath !== '' && str_starts_with($absolutePath, '/storage/package-samples/')) {
                return '/media/'.ltrim(substr($absolutePath, strlen('/storage/')), '/');
            }

            return $value;
        }

        if (! str_contains($value, '/')) {
            return '/media/package-samples/'.$value;
        }

        $normalized = '/'.ltrim(str_replace('\\', '/', $value), '/');

        if (str_starts_with($normalized, '/media/package-samples/')) {
            return $normalized;
        }

        if (str_starts_with($normalized, '/storage/package-samples/')) {
            return '/media/'.ltrim(substr($normalized, strlen('/storage/')), '/');
        }

        if (str_starts_with($normalized, '/package-samples/')) {
            return '/media/'.ltrim($normalized, '/');
        }

        return $normalized;
    }
}
