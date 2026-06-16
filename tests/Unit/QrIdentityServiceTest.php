<?php

use App\Models\QrIdentity;
use App\Services\QrIdentityService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class QrIdentityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $container->instance('config', new Repository([
            'app' => ['key' => 'base64:test-key-for-qr-identity-service'],
        ]));

        Container::setInstance($container);
    }

    public function test_it_hashes_tokens_without_returning_plaintext(): void
    {
        $service = new QrIdentityService();

        $hash = $service->hashToken('secret-token');

        $this->assertSame(64, strlen($hash));
        $this->assertNotSame('secret-token', $hash);
        $this->assertSame($hash, $service->hashToken('secret-token'));
    }

    public function test_it_rejects_unsupported_subjects(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = new QrIdentityService();
        $service->subjectTypeFor(new class extends Model {
        });
    }

    public function test_qr_identity_status_helper_detects_active_records(): void
    {
        $identity = new QrIdentity(['status' => QrIdentity::STATUS_ACTIVE]);
        $this->assertTrue($identity->isActive());

        $identity->status = QrIdentity::STATUS_REVOKED;
        $this->assertFalse($identity->isActive());
    }
}
