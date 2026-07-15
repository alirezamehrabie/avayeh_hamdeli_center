<?php

namespace Tests\Unit;

use App\Exceptions\AiCaseAssistantException;
use App\Models\Guardian;
use App\Models\Person;
use App\Services\Ai\OpenAiFollowUpMessageGenerator;
use App\Services\Ai\SensitiveTextRedactor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiFollowUpMessageGeneratorTest extends TestCase
{
    public function test_it_requests_a_structured_draft_without_direct_identifiers(): void
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
                            'can_generate' => true,
                            'message' => 'سلام، لطفا مدارک تحصیلی را تا پایان هفته تحویل دهید. سپاسگزاریم.',
                            'review_note' => 'زمان تحویل را پیش از ارسال بررسی کنید.',
                        ], JSON_UNESCAPED_UNICODE),
                    ]],
                ]],
            ]),
        ]);

        $guardian = new Guardian([
            'first_name' => 'Maryam',
            'last_name' => 'Mehrabi',
            'national_code' => '1234567890',
            'guardian_phone_number' => '09121234567',
        ]);
        $person = new Person([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '0012345678',
            'phone_number' => '09121111111',
            'person_code' => 'P-2040',
        ]);
        $person->setRelation('guardian', $guardian);

        $draft = (new OpenAiFollowUpMessageGenerator(new SensitiveTextRedactor))->generate(
            $person,
            'guardian',
            'sms',
            'document_request',
            'respectful',
            'به Maryam Mehrabi با شماره 09121234567 و ایمیل ali@example.com بگویید مدارک تحصیلی را تا پایان هفته تحویل دهد.',
        );

        $this->assertTrue($draft['can_generate']);
        $this->assertStringContainsString('مدارک تحصیلی', $draft['message']);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();
            $context = json_decode($payload['input'] ?? '{}', true);
            $encodedContext = json_encode($context, JSON_UNESCAPED_UNICODE);

            return $request->url() === 'https://api.openai.test/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && ($payload['model'] ?? null) === 'test-model'
                && ($payload['store'] ?? null) === false
                && ($payload['text']['format']['type'] ?? null) === 'json_schema'
                && ($payload['text']['format']['strict'] ?? null) === true
                && ($context['recipient'] ?? null) === 'guardian'
                && ($context['channel'] ?? null) === 'sms'
                && ! str_contains($encodedContext, 'Maryam')
                && ! str_contains($encodedContext, 'Mehrabi')
                && ! str_contains($encodedContext, '09121234567')
                && ! str_contains($encodedContext, 'ali@example.com');
        });
    }

    public function test_it_blocks_a_generated_draft_that_contains_identifiers(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'test-model',
            'services.openai.base_url' => 'https://api.openai.test/v1',
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'can_generate' => true,
                    'message' => 'سلام Ali، با شماره 09121111111 تماس بگیرید.',
                    'review_note' => 'نام Ali را بررسی کنید.',
                ], JSON_UNESCAPED_UNICODE),
            ]),
        ]);

        $person = new Person([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'phone_number' => '09121111111',
        ]);

        $draft = (new OpenAiFollowUpMessageGenerator(new SensitiveTextRedactor))->generate(
            $person,
            'beneficiary',
            'sms',
            'case_follow_up',
            'respectful',
            'درخواست شود برای پیگیری پرونده مراجعه کند.',
        );

        $this->assertFalse($draft['can_generate']);
        $this->assertSame('', $draft['message']);
        $this->assertStringContainsString('اطلاعات شناسایی', $draft['review_note']);
    }

    public function test_it_handles_a_structured_insufficient_details_response(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'test-model',
            'services.openai.base_url' => 'https://api.openai.test/v1',
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'can_generate' => false,
                    'message' => '',
                    'review_note' => 'موضوع پیگیری باید مشخص شود.',
                ], JSON_UNESCAPED_UNICODE),
            ]),
        ]);

        $draft = (new OpenAiFollowUpMessageGenerator(new SensitiveTextRedactor))->generate(
            new Person,
            'other',
            'whatsapp',
            'custom',
            'formal',
            'یک پیام برای او بنویس.',
        );

        $this->assertFalse($draft['can_generate']);
        $this->assertSame('', $draft['message']);
        $this->assertSame('موضوع پیگیری باید مشخص شود.', $draft['review_note']);
    }

    public function test_it_blocks_a_draft_that_exceeds_the_channel_limit(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'test-model',
            'services.openai.base_url' => 'https://api.openai.test/v1',
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'can_generate' => true,
                    'message' => str_repeat('الف', 501),
                    'review_note' => '',
                ], JSON_UNESCAPED_UNICODE),
            ]),
        ]);

        $draft = (new OpenAiFollowUpMessageGenerator(new SensitiveTextRedactor))->generate(
            new Person,
            'beneficiary',
            'sms',
            'case_follow_up',
            'respectful',
            'برای پیگیری پرونده درخواست مراجعه شود.',
        );

        $this->assertFalse($draft['can_generate']);
        $this->assertSame('', $draft['message']);
        $this->assertStringContainsString('محدودیت طول', $draft['review_note']);
    }

    public function test_it_rejects_model_refusals(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'test-model',
            'services.openai.base_url' => 'https://api.openai.test/v1',
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'refusal',
                        'refusal' => 'Unable to generate.',
                    ]],
                ]],
            ]),
        ]);

        $this->expectException(AiCaseAssistantException::class);

        (new OpenAiFollowUpMessageGenerator(new SensitiveTextRedactor))->generate(
            new Person,
            'beneficiary',
            'sms',
            'case_follow_up',
            'respectful',
            'برای پیگیری پرونده درخواست مراجعه شود.',
        );
    }
}
