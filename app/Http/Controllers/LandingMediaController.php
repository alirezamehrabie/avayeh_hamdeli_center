<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams landing-page images uploaded through the admin panel.
 *
 * Mirrors ServiceCategoryThumbnailController (the /storage symlink is unreliable
 * on this hosting layout), but WITHOUT the auth guard: these images are shown on
 * the public landing page to guests. The path pattern is the only gate, so it is
 * pinned to the landing upload folders and the webp extension. File names are
 * UUID-based and immutable, hence the long public cache lifetime.
 */
class LandingMediaController extends Controller
{
    public const PATH_PATTERN = 'landing/(?:banners|services)/[A-Za-z0-9._-]+\.webp';

    public function show(string $path): StreamedResponse
    {
        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        abort_unless(preg_match('#^'.self::PATH_PATTERN.'$#', $normalized) === 1, 404);
        abort_unless(Storage::disk('public')->exists($normalized), 404);

        return Storage::disk('public')->response(
            $normalized,
            basename($normalized),
            ['Cache-Control' => 'public, max-age=31536000, immutable'],
        );
    }
}
