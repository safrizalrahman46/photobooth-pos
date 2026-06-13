<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller untuk melayani QR images sebagai fallback jika storage symlink gagal.
 * Mengikuti pattern PackageSamplePhotoController untuk konsistensi.
 */
class QrMediaController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        // Normalisasi path dan security validation
        $normalizedPath = trim(str_replace('\\', '/', $path), '/');

        // Prevent directory traversal attacks
        if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
            abort(404);
        }

        $diskPath = 'qr/'.ltrim($normalizedPath, '/');

        // Verifikasi file exists di storage
        if (! Storage::disk('public')->exists($diskPath)) {
            abort(404);
        }

        // Additional security: hanya allow image files
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
        
        if (! in_array($extension, $allowedExtensions, true)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($diskPath);

        return response()->file($absolutePath, [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Type' => $this->getContentType($extension),
        ]);
    }

    /**
     * Get proper content type untuk image files
     */
    private function getContentType(string $extension): string
    {
        return match($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream'
        };
    }
}