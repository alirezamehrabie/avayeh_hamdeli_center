<?php

namespace Tests\Feature;

use Tests\TestCase;

class MagazineDevotionalTest extends TestCase
{
    public function test_spiritual_page_is_accessible_and_lists_devotional_items(): void
    {
        $response = $this->get('/magazine/spiritual');

        $response->assertOk();
        $response->assertSee('زیارت عاشورا');
        $response->assertSee('دعای توسل');
        $response->assertSee('دعای عهد');
        $response->assertSee(route('magazine.ahd'));
    }

    public function test_ahd_page_displays_correct_title_and_text(): void
    {
        $response = $this->get('/magazine/ahd');

        $response->assertOk();
        $response->assertSee('دعای عهد');
        $response->assertSee('اَللّهُمَّ رَبَّ النُّورِ الْعَظيمِ');
        $response->assertSee('اَلْعَجَلَ الْعَجَلَ يا مَوْلاىَ يا صــاحِبَ الزَّمــانِ');
    }
}
