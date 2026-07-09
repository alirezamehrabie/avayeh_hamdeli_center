<?php

namespace Tests\Unit;

use App\Models\Service;
use PHPUnit\Framework\TestCase;

class ServiceTypeDisplayMetaTest extends TestCase
{
    public function test_it_returns_compact_report_metadata_for_individual_services(): void
    {
        $meta = Service::typeDisplayMeta('individual');

        $this->assertSame('شخصی', $meta['label']);
        $this->assertSame('person', $meta['icon']);
    }

    public function test_it_returns_compact_report_metadata_for_family_services(): void
    {
        $meta = Service::typeDisplayMeta('family');

        $this->assertSame('خانوادگی', $meta['label']);
        $this->assertSame('family', $meta['icon']);
    }

    public function test_it_exposes_compact_type_filter_options(): void
    {
        $this->assertSame([
            'individual' => 'شخصی',
            'family' => 'خانوادگی',
        ], Service::typeDisplayOptions());
    }
}
