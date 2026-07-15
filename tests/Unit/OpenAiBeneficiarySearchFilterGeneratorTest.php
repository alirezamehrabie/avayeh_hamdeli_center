<?php

namespace Tests\Unit;

use App\Services\Ai\OpenAiBeneficiarySearchFilterGenerator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiBeneficiarySearchFilterGeneratorTest extends TestCase
{
    public function test_it_requests_strict_filters_and_maps_them_to_the_advanced_filter_structure(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'test-model',
            'services.openai.base_url' => 'https://api.openai.test/v1',
            'services.openai.timeout' => 5,
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'interpretation' => 'Children with disabilities without recent services',
                    'filters' => [
                        ['field' => 'age', 'operator' => 'lte', 'value' => '17'],
                        ['field' => 'disability_status_type', 'operator' => 'eq', 'value' => 'has:1'],
                        ['field' => 'months_without_service', 'operator' => 'gte', 'value' => '6'],
                    ],
                    'unresolved' => [],
                ]),
            ]),
        ]);

        $result = (new OpenAiBeneficiarySearchFilterGenerator)->generate('Show matching children', [
            'age' => ['label' => 'Age', 'type' => 'number', 'options' => []],
            'disability_status_type' => [
                'label' => 'Disability',
                'type' => 'select',
                'options' => ['has:1' => 'Has disability', 'has:0' => 'No disability'],
            ],
            'months_without_service' => ['label' => 'Months without service', 'type' => 'number', 'options' => []],
        ]);

        $this->assertSame([
            ['field' => 'age', 'type' => 'number', 'operator' => 'lte', 'value' => '17'],
            ['field' => 'disability_status_type', 'type' => 'select', 'value' => 'has:1'],
            ['field' => 'months_without_service', 'type' => 'number', 'operator' => 'gte', 'value' => '6'],
        ], $result['filters']);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://api.openai.test/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && ($payload['store'] ?? null) === false
                && ($payload['text']['format']['type'] ?? null) === 'json_schema'
                && ($payload['text']['format']['strict'] ?? null) === true
                && in_array('months_without_service', $payload['text']['format']['schema']['properties']['filters']['items']['properties']['field']['enum'] ?? [], true);
        });
    }

    public function test_it_rejects_unknown_select_values_and_unreasonable_numbers_after_ai_output(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'test-model',
            'services.openai.base_url' => 'https://api.openai.test/v1',
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'interpretation' => 'Invalid values',
                    'filters' => [
                        ['field' => 'disability_status_type', 'operator' => 'eq', 'value' => 'has:9'],
                        ['field' => 'age', 'operator' => 'lte', 'value' => '900'],
                    ],
                    'unresolved' => ['Values could not be matched.'],
                ]),
            ]),
        ]);

        $result = (new OpenAiBeneficiarySearchFilterGenerator)->generate('Invalid request', [
            'age' => ['label' => 'Age', 'type' => 'number', 'options' => []],
            'disability_status_type' => [
                'label' => 'Disability',
                'type' => 'select',
                'options' => ['has:1' => 'Has disability'],
            ],
        ]);

        $this->assertSame([], $result['filters']);
        $this->assertSame(['Values could not be matched.'], $result['unresolved']);
    }
}
