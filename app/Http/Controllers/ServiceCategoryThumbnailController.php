<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams category thumbnails straight from the public disk.
 *
 * The classic /storage symlink is unreliable on this hosting layout (the app
 * lives in public_html/laravel while the document root differs), so every
 * category image consumer goes through this route instead. File names are
 * UUID-based and immutable, hence the long cache lifetime.
 */
class ServiceCategoryThumbnailController extends Controller
{
    public const PATH_PATTERN = 'service-categories/\d+/[A-Za-z0-9._-]+';

    public function show(string $path): StreamedResponse
    {
        abort_unless(auth()->check(), 403);

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        abort_unless(preg_match('#^'.self::PATH_PATTERN.'$#', $normalized) === 1, 404);
        abort_unless(Storage::disk('public')->exists($normalized), 404);

        return Storage::disk('public')->response(
            $normalized,
            basename($normalized),
            ['Cache-Control' => 'private, max-age=31536000, immutable'],
        );
    }
}
