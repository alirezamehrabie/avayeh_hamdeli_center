<?php

namespace App\Support\Landing;

use App\Http\Controllers\LandingMediaController;
use Illuminate\Support\Facades\Route;

class LandingMediaUrl
{
    /**
     * Canonical display URL for a stored landing image path.
     *
     * Two shapes are supported on purpose:
     *  - legacy public-folder assets shipped with the repo (images/landing/...),
     *    served as real static files by the web server;
     *  - admin-uploaded assets kept on the public disk (landing/...), which are
     *    streamed through the public landing-media route because the
     *    public/storage symlink cannot be relied on in this deployment layout.
     */
    public static function for(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', (string) $path), '/');

        if (str_starts_with($normalized, LandingImageCatalog::ROOT.'/')) {
            return asset($normalized);
        }

        if (preg_match('#^'.LandingMediaController::PATH_PATTERN.'$#', $normalized) !== 1) {
            return null;
        }

        return Route::has('landing.media.show')
            ? route('landing.media.show', ['path' => $normalized])
            : null;
    }
}
