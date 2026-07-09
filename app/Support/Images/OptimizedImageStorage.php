<?php

namespace App\Support\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OptimizedImageStorage
{
    protected const MAX_DIMENSION = 1600;

    protected const TARGET_MAX_BYTES = 512000;

    public function store(UploadedFile $file, string $directory, string $disk = 'public', string $filenamePrefix = 'image'): string
    {
        $image = $this->createImageResource($file);
        $image = $this->applyExifOrientation($file, $image);

        $optimizedBinary = $this->encodeOptimizedJpeg($image);
        imagedestroy($image);

        $path = trim($directory, '/').'/'.$filenamePrefix.'-'.Str::uuid()->toString().'.jpg';

        if (! Storage::disk($disk)->put($path, $optimizedBinary, ['visibility' => 'public'])) {
            throw new RuntimeException('Failed to store optimized image.');
        }

        return $path;
    }

    public function delete(?string $path, string $disk = 'public'): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk($disk)->delete((string) $path);
    }

    protected function createImageResource(UploadedFile $file): \GdImage
    {
        $binary = @file_get_contents($file->getRealPath());

        if ($binary === false) {
            throw new RuntimeException('Failed to read uploaded image.');
        }

        $image = @imagecreatefromstring($binary);

        if (! $image instanceof \GdImage) {
            throw new RuntimeException('Uploaded file is not a readable image.');
        }

        return $image;
    }

    protected function applyExifOrientation(UploadedFile $file, \GdImage $image): \GdImage
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['jpg', 'jpeg'], true) || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = (int) ($exif['Orientation'] ?? 1);

        return match ($orientation) {
            3 => imagerotate($image, 180, 0) ?: $image,
            6 => imagerotate($image, -90, 0) ?: $image,
            8 => imagerotate($image, 90, 0) ?: $image,
            default => $image,
        };
    }

    protected function encodeOptimizedJpeg(\GdImage $image): string
    {
        $canvas = $this->resizeToCanvas($image);
        $qualities = [84, 80, 76, 72, 68, 64, 60];
        $bestBinary = null;

        foreach ($qualities as $quality) {
            ob_start();
            imagejpeg($canvas, null, $quality);
            $binary = (string) ob_get_clean();

            if ($binary === '') {
                continue;
            }

            $bestBinary = $binary;

            if (strlen($binary) <= self::TARGET_MAX_BYTES) {
                break;
            }
        }

        imagedestroy($canvas);

        if ($bestBinary === null) {
            throw new RuntimeException('Failed to encode optimized image.');
        }

        return $bestBinary;
    }

    protected function resizeToCanvas(\GdImage $image): \GdImage
    {
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            throw new RuntimeException('Uploaded image has invalid dimensions.');
        }

        $scale = min(
            1,
            self::MAX_DIMENSION / $sourceWidth,
            self::MAX_DIMENSION / $sourceHeight
        );

        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $canvas instanceof \GdImage) {
            throw new RuntimeException('Failed to prepare image canvas.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        return $canvas;
    }
}
