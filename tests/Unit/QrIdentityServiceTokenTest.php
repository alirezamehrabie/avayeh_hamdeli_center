<?php

namespace Tests\Unit;

use App\Models\Person;
use App\Models\QrIdentity;
use App\Services\QrIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrIdentityServiceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_extract_token_returns_bare_tokens_unchanged(): void
    {
        $service = app(QrIdentityService::class);

        $this->assertSame('abc123', $service->extractToken('abc123'));
        $this->assertSame('abc123', $service->extractToken('  abc123  '));
        $this->assertSame('PQR-ABCDEFGH', $service->extractToken('PQR-ABCDEFGH'));
    }

    public function test_extract_token_reads_token_from_resolve_url(): void
    {
        $service = app(QrIdentityService::class);

        $this->assertSame('tok-1', $service->extractToken('https://example.test/qr/r/tok-1'));
        $this->assertSame('tok-1', $service->extractToken('http://example.test/qr/r/tok-1?x=1#frag'));
        $this->assertSame('tok 1', $service->extractToken('https://example.test/qr/r/tok%201'));
    }

    public function test_extract_token_rejects_empty_and_foreign_urls(): void
    {
        $service = app(QrIdentityService::class);

        $this->assertNull($service->extractToken(''));
        $this->assertNull($service->extractToken('   '));
        $this->assertNull($service->extractToken('https://example.test/other/path'));
    }

    public function test_resolve_public_code_finds_active_identity_case_insensitively(): void
    {
        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $service = app(QrIdentityService::class);
        $identity = $service->issueFor($person)['identity'];

        $resolved = $service->resolvePublicCode(strtolower($identity->public_code));

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($identity));
        $this->assertTrue($resolved->relationLoaded('subject'));
        $this->assertNotNull($resolved->fresh()->last_scanned_at);
    }

    public function test_resolve_public_code_rejects_revoked_identity_and_trashed_subject(): void
    {
        $person = Person::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Karimi',
            'national_id' => '2234567890',
            'person_code' => '14002',
        ]);

        $service = app(QrIdentityService::class);
        $identity = $service->issueFor($person)['identity'];

        $identity->update(['status' => QrIdentity::STATUS_REVOKED]);
        $this->assertNull($service->resolvePublicCode($identity->public_code));

        $identity->update(['status' => QrIdentity::STATUS_ACTIVE]);
        $person->delete();
        $this->assertNull($service->resolvePublicCode($identity->public_code));

        $this->assertNull($service->resolvePublicCode('PQR-DOESNOTEXIST'));
    }
}
