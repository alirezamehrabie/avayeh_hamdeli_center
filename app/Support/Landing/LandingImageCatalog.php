<?php

namespace App\Support\Landing;

class LandingImageCatalog
{
    public const ROOT = 'images/landing';

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Relative public paths (e.g. images/landing/slide-1.jpg) of every display
     * image under public/images/landing, sorted by directory then filename.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $root = public_path(self::ROOT);

        if (! is_dir($root)) {
            return [];
        }

        $paths = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());

            if (! in_array($extension, self::EXTENSIONS, true)) {
                continue;
            }

            $relative = trim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');
            $paths[] = self::ROOT.'/'.$relative;
        }

        sort($paths);

        return $paths;
    }

    public static function exists(string $relativePath): bool
    {
        $normalized = trim(str_replace('\\', '/', $relativePath), '/');

        // Reject traversal attempts before touching the filesystem.
        if ($normalized === '' || str_contains($normalized, '..')) {
            return false;
        }

        if (! str_starts_with($normalized, self::ROOT.'/')) {
            return false;
        }

        $extension = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));

        if (! in_array($extension, self::EXTENSIONS, true)) {
            return false;
        }

        return is_file(public_path($normalized));
    }
}
