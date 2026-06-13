<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Package;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncOrphanImages extends Command
{
    protected $signature = 'sync:orphan-images
        {--dry-run : Only scan and report, do not modify anything}';

    protected $description = 'Scan storage directories and report orphan image files not linked to any DB record';

    public function handle(): int
    {
        $this->info('Scanning for orphan image files...');

        $orphanPackagePhotos = $this->scanPackagePhotos();
        $orphanTransferProofs = $this->scanTransferProofs();

        $totalOrphans = count($orphanPackagePhotos) + count($orphanTransferProofs);

        if ($totalOrphans === 0) {
            $this->info('No orphan files found.');
            return 0;
        }

        $this->newLine();
        $this->warn("Found {$totalOrphans} orphan file(s):");

        if ($orphanPackagePhotos) {
            $this->newLine();
            $this->line('--- Package Sample Photos ---');
            foreach ($orphanPackagePhotos as $file) {
                $this->line("  {$file}");
            }
        }

        if ($orphanTransferProofs) {
            $this->newLine();
            $this->line('--- Transfer Proofs ---');
            foreach ($orphanTransferProofs as $file) {
                $this->line("  {$file}");
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run completed. No changes were made.');
            return 0;
        }

        return 0;
    }

    private function scanPackagePhotos(): array
    {
        $disk = Storage::disk('public');
        $files = $disk->files('package-samples');
        $orphans = [];

        $packages = Package::query()
            ->whereNotNull('sample_photos')
            ->get(['id', 'sample_photos']);

        foreach ($files as $file) {
            $filename = basename($file);
            $found = false;

            foreach ($packages as $package) {
                $photos = $package->sample_photos ?? [];

                foreach ($photos as $photo) {
                    if (str_contains($photo, $filename)) {
                        $found = true;
                        break 2;
                    }
                }
            }

            if (!$found) {
                $orphans[] = $file;
            }
        }

        return $orphans;
    }

    private function scanTransferProofs(): array
    {
        $disk = Storage::disk('public');
        $files = $disk->files('transfer-proofs');
        $orphans = [];

        $bookings = Booking::query()
            ->whereNotNull('transfer_proof_path')
            ->get(['id', 'transfer_proof_path']);

        $knownPaths = $bookings->pluck('transfer_proof_path')->filter()->all();

        foreach ($files as $file) {
            if (!in_array($file, $knownPaths, true)) {
                $orphans[] = $file;
            }
        }

        return $orphans;
    }
}
