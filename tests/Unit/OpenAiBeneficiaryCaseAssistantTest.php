<?php

namespace Tests\Unit;

use App\Models\Person;
use App\Services\Ai\BeneficiaryCaseContextBuilder;
use App\Services\Ai\BeneficiaryCaseMetricsBuilder;
use App\Services\Ai\OpenAiBeneficiaryCaseAssistant;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiBeneficiaryCaseAssistantTest extends TestCase
{
    public function test_it_requests_structured_analysis_and_parses_response_output(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'test-model',
            'services.openai.base_url' => 'https://api.openai.test/v1',
            'services.openai.timeout' => 5,
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode([
                            'summary' => 'خلاصه پرونده',
                            'reminders' => [
                                ['title' => 'پیگیری درمان', 'category' => 'today_tasks'],
                            ],
                        ], JSON_UNESCAPED_UNICODE),
                    ]],
                ]],
            ]),
        ]);

        $analysis = (new OpenAiBeneficiaryCaseAssistant(
            new BeneficiaryCaseContextBuilder,
            new BeneficiaryCaseMetricsBuilder,
        ))->generate(
            new Person(['gender' => 'female']),
            new Collection,
            new Collection,
            new Collection,
            [
                'direct_services_count' => 3,
                'direct_services_value' => 900000,
                'family_services_count' => 2,
                'family_services_value' => 400000,
                'activity_attendances_count' => 4,
                'manual_records_count' => 1,
                'manual_records_amount' => 250000,
            ],
        );

        $this->assertSame('خلاصه پرونده', $analysis['summary']);
        $this->assertSame('پیگیری درمان', $analysis['reminders'][0]['title']);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();
            $context = json_decode($payload['input'] ?? '{}', true);

            return $request->url() === 'https://api.openai.test/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && ($payload['model'] ?? null) === 'test-model'
                && ($payload['text']['format']['type'] ?? null) === 'json_schema'
                && ($payload['text']['format']['strict'] ?? null) === true
                && ($context['metrics']['services']['total_count'] ?? null) === 5
                && ($context['metrics']['services']['total_value_rials'] ?? null) === 1300000
                && ($context['metrics']['activities']['attendance_count'] ?? null) === 4
                && ($context['metrics']['case_records']['count'] ?? null) === 1;
        });
    }
}
