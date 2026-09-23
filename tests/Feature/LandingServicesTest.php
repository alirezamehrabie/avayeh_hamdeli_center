<?php

namespace Tests\Feature;

use App\Models\LandingServiceCard;
use App\Models\User;
use App\Support\Landing\LandingContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        LandingContent::flush();
        parent::tearDown();
    }

    public function test_landing_page_displays_view_all_services_button_linking_to_services_page(): void
    {
        $response = $this->get('/landing');

        $response->assertOk();
        $response->assertSee('مشاهده همه خدمات');
        $response->assertSee('View All Services');
        $response->assertSee(route('landing.services'));
    }

    public function test_landing_page_displays_charity_and_child_aid_sentence(): void
    {
        $response = $this->get('/landing');

        $response->assertOk();
        $response->assertSee('آوای همدلی؛ همراهی مهربان برای یاری کودکان نیازمند');
    }

    public function test_landing_page_displays_magazine_section_title(): void
    {
        $response = $this->get('/landing');

        $response->assertOk();
        $response->assertSee('مجلۀ همدلی');
        $response->assertSee('id="magazine-title"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="magazine-title"'));
        $response->assertSee('id="magazine-card-title"', false);
        $response->assertSee('aria-labelledby="magazine-card-title"', false);
    }

    public function test_services_page_is_accessible_and_renders_list_view_with_services_and_images(): void
    {
        $services = LandingContent::services();
        $this->assertNotEmpty($services);

        $response = $this->get('/services');

        $response->assertOk();
        $response->assertViewIs('landing.services');
        $response->assertViewHas('services', $services);

        foreach ($services as $service) {
            $response->assertSee($service['title']);
            $response->assertSee($service['image']);
        }
    }

    public function test_services_page_displays_database_cards_when_available(): void
    {
        $user = User::factory()->create();

        LandingServiceCard::create([
            'image_path' => 'images/landing/services/custom-test-service.png',
            'title' => 'خدمت تست اختصاصی آوای همدلی',
            'rail_row' => 1,
            'sort_id' => 1,
            'active_status' => true,
            'created_by' => $user->id,
        ]);

        LandingContent::flush();

        $response = $this->get('/services');

        $response->assertOk();
        $response->assertSee('خدمت تست اختصاصی آوای همدلی');
        $response->assertSee('images/landing/services/custom-test-service.png');
    }

    public function test_services_page_does_not_display_site_footer(): void
    {
        $response = $this->get('/services');

        $response->assertOk();
        $response->assertDontSee('id="site-footer"', false);
        $response->assertDontSee('ورود پرسنل و مدیران');
    }
}
