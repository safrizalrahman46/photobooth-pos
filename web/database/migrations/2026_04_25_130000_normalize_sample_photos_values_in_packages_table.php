<?php

use App\Models\Package;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('packages')
            ->select(['id', 'sample_photos'])
            ->whereNotNull('sample_photos')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $raw = $row->sample_photos;
                    $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    $normalized = Package::resolveSamplePhotoUrls($decoded);
                    $original = array_values(array_filter(array_map(
                        fn ($item): string => trim((string) $item),
                        $decoded
                    ), fn (string $item): bool => $item !== ''));

                    if ($normalized === $original) {
                        continue;
                    }

                    DB::table('packages')
                        ->where('id', (int) $row->id)
                        ->update([
                            'sample_photos' => json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            'updated_at' => now(),
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // no-op
    }
};
