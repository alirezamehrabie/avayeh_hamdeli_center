<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceCategoryThumbnailTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_stream_a_category_thumbnail(): void
    {
        Storage::fake('public');

        $path = 'service-categories/210/category-0f8f.jpg';
        Storage::disk('public')->put($path, $this->jpegBytes());

        $this->actingAs(User::factory()->create());

        $response = $this->get(route('media.service-category-thumbnails.show', ['path' => $path]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('max-age=31536000', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
        $this->assertSame($this->jpegBytes(), $response->streamedContent());
    }

    public function test_guests_cannot_stream_category_thumbnails(): void
    {
        $response = $this->get(route('media.service-category-thumbnails.show', [
            'path' => 'service-categories/210/category-0f8f.jpg',
        ]));

        $response->assertRedirect();
    }

    public function test_paths_outside_service_categories_are_not_served(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $this->get('/media/other/210/x.jpg')->assertNotFound();
        $this->get('/media/service-categories/%2E%2E/%2E%2E/.env')->assertNotFound();
        $this->get('/media/service-categories/abc/category-0f8f.jpg')->assertNotFound();
    }

    public function test_missing_thumbnail_file_returns_404(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $this->get(route('media.service-category-thumbnails.show', [
            'path' => 'service-categories/210/category-missing.jpg',
        ]))->assertNotFound();
    }

    private function jpegBytes(): string
    {
        $image = imagecreatetruecolor(4, 4);
        ob_start();
        imagejpeg($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary;
    }
}
