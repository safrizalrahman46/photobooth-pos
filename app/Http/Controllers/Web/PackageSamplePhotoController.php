<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PackageSamplePhotoController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        $normalizedPath = trim(str_replace('\\', '/', $path), '/');

        if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
            abort(404);
        }

        $diskPath = 'package-samples/'.ltrim($normalizedPath, '/');

        if (! Storage::disk('public')->exists($diskPath)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($diskPath);

        return response()->file($absolutePath, [
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
