<?php

namespace Tests\Feature;

use App\Contracts\Ai\GeneratesBeneficiarySearchFilters;
use App\Helpers\Morilog\Jalalian;
use App\Livewire\People\AdvancedFilterBuilder;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class AdvancedBeneficiaryAiSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_filters_require_confirmation_before_they_replace_current_filters(): void
    {
        $this->actingAs($this->manager());

        $this->app->instance(GeneratesBeneficiarySearchFilters::class, new class implements GeneratesBeneficiarySearchFilters
        {
            public function generate(string $query, array $catalog): array
            {
                return [
                    'interpretation' => 'کودکان دارای معلولیت بدون خدمت در شش ماه اخیر',
                    'filters' => [
                        ['field' => 'age', 'type' => 'number', 'operator' => 'lte', 'value' => '17'],
                        ['field' => 'disability_status_type', 'type' => 'select', 'value' => 'has:1'],
                        ['field' => 'months_without_service', 'type' => 'number', 'operator' => 'gte', 'value' => '6'],
                    ],
                    'summaries' => ['سن <= 17', 'معلولیت: دارای معلولیت', 'ماه‌های بدون خدمت >= 6'],
                    'unresolved' => [],
                ];
            }
        });

        Livewire::test(AdvancedFilterBuilder::class)
            ->set('filters', [
                ['field' => 'gender', 'type' => 'select', 'value' => 'female'],
            ])
            ->set('aiSearchQuery', 'کودکان دارای معلولیت بدون خدمت در شش ماه اخیر')
            ->call('interpretAiSearch')
            ->assertSet('filters.0.field', 'gender')
            ->assertSet('pendingAiSearch.filters.0.field', 'age')
            ->assertSee('فیلترهای برداشت‌شده برای تأیید')
            ->call('confirmAiSearch')
            ->assertSet('pendingAiSearch', null)
            ->assertSet('filters.0.field', 'age')
            ->assertSet('filters.1.field', 'disability_status_type')
            ->assertSet('filters.2.field', 'months_without_service');
    }

    public function test_months_without_service_uses_indexable_recipient_and_delivery_date_subqueries(): void
    {
        $component = app(AdvancedFilterBuilder::class);
        $component->mount();
        $component->filters = [
            ['field' => 'months_without_service', 'type' => 'number', 'operator' => 'gte', 'value' => '6'],
        ];

        $method = new ReflectionMethod($component, 'buildQuery');
        $query = $method->invoke($component);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertGreaterThanOrEqual(2, substr_count(strtolower($query->toSql()), 'service_deliveries'));
        $this->assertCount(2, array_filter(
            $query->getBindings(),
            fn (mixed $binding): bool => is_string($binding) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $binding) === 1
        ));
    }

    public function test_age_filter_includes_seventeen_year_old_beneficiaries_and_excludes_eighteen_year_olds(): void
    {
        $today = Jalalian::now();
        $child = $this->person('AI1701', '9100000001', $today->getYear() - 17, $today->getMonth(), $today->getDay());
        $adult = $this->person('AI1801', '9100000002', $today->getYear() - 18, $today->getMonth(), $today->getDay());

        $component = app(AdvancedFilterBuilder::class);
        $component->mount();
        $component->filters = [
            ['field' => 'age', 'type' => 'number', 'operator' => 'lte', 'value' => '17'],
        ];

        $method = new ReflectionMethod($component, 'buildQuery');
        $ids = $method->invoke($component)->pluck('id');

        $this->assertTrue($ids->contains($child->id));
        $this->assertFalse($ids->contains($adult->id));
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    private function person(string $code, string $nationalId, int $year, int $month, int $day): Person
    {
        return Person::query()->create([
            'person_code' => $code,
            'national_id' => $nationalId,
            'first_name' => 'AI',
            'last_name' => 'Search',
            'birth_year' => $year,
            'birth_month' => $month,
            'birth_day' => $day,
        ]);
    }
}
