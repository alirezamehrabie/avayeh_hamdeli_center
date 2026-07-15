<?php

namespace Tests\Unit;

use App\Models\Person;
use App\Services\Ai\BeneficiaryCaseContextBuilder;
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

        $analysis = (new OpenAiBeneficiaryCaseAssistant(new BeneficiaryCaseContextBuilder))->generate(
            new Person(['gender' => 'female']),
            new Collection,
            new Collection,
            new Collection,
        );

        $this->assertSame('خلاصه پرونده', $analysis['summary']);
        $this->assertSame('پیگیری درمان', $analysis['reminders'][0]['title']);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://api.openai.test/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && ($payload['model'] ?? null) === 'test-model'
                && ($payload['text']['format']['type'] ?? null) === 'json_schema'
                && ($payload['text']['format']['strict'] ?? null) === true;
        });
    }
}
