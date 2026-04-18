<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins\Unit;

use Capell\Plugins\Data\AnystackLicenseValidationData;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Services\AnystackClient;
use Capell\Tests\Plugins\PluginsTestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AnystackClientTest extends PluginsTestCase
{
    private AnystackClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new AnystackClient(
            'https://api.anystack.sh',
            10,
        );
    }

    public function test_validates_active_license(): void
    {
        Http::fake(fn () => Http::response(json_encode([
            'data' => [
                'id' => 'license-123',
                'key' => 'test-key',
                'suspended' => false,
                'expires_at' => '2099-12-31T00:00:00+00:00',
                'contact_id' => 'contact-456',
                'policy_id' => 'policy-789',
                'activations' => 3,
                'max_activations' => 5,
                'created_at' => '2021-07-05T09:42:06+00:00',
                'updated_at' => '2021-07-14T10:56:24+00:00',
            ],
        ]), 200));

        $result = $this->client->validateLicense('test-product', 'test-key');

        $this->assertInstanceOf(AnystackLicenseValidationData::class, $result);
        $this->assertTrue($result->valid);
        $this->assertEquals(LicenseStatus::Active, $result->status);
        $this->assertEquals('test-product', $result->product);
        $this->assertNotNull($result->expiresAt);
    }

    public function test_validates_expired_license(): void
    {
        Http::fake(fn () => Http::response(json_encode([
            'data' => [
                'id' => 'license-123',
                'key' => 'test-key',
                'suspended' => false,
                'expires_at' => '2020-01-01T00:00:00+00:00',
                'contact_id' => 'contact-456',
                'policy_id' => 'policy-789',
                'activations' => 0,
                'max_activations' => 5,
                'created_at' => '2021-07-05T09:42:06+00:00',
                'updated_at' => '2021-07-14T10:56:24+00:00',
            ],
        ]), 200));

        $result = $this->client->validateLicense('test-product', 'test-key');

        $this->assertFalse($result->valid);
        $this->assertEquals(LicenseStatus::Expired, $result->status);
    }

    public function test_validates_suspended_license(): void
    {
        Http::fake(fn () => Http::response(json_encode([
            'data' => [
                'id' => 'license-123',
                'key' => 'test-key',
                'suspended' => true,
                'expires_at' => '2099-12-31T00:00:00+00:00',
                'contact_id' => 'contact-456',
                'policy_id' => 'policy-789',
                'activations' => 3,
                'max_activations' => 5,
                'created_at' => '2021-07-05T09:42:06+00:00',
                'updated_at' => '2021-07-14T10:56:24+00:00',
            ],
        ]), 200));

        $result = $this->client->validateLicense('test-product', 'test-key');

        $this->assertFalse($result->valid);
        $this->assertEquals(LicenseStatus::Revoked, $result->status);
    }

    public function test_includes_site_fingerprint_in_request(): void
    {
        Http::fake(fn () => Http::response(json_encode([
            'data' => [
                'id' => 'license-123',
                'key' => 'test-key',
                'suspended' => false,
                'expires_at' => '2099-12-31T00:00:00+00:00',
                'contact_id' => 'contact-456',
                'policy_id' => 'policy-789',
                'activations' => 3,
                'max_activations' => 5,
                'created_at' => '2021-07-05T09:42:06+00:00',
                'updated_at' => '2021-07-14T10:56:24+00:00',
            ],
        ]), 200));

        $this->client->validateLicense('test-product', 'test-key', 'site-fingerprint-123');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return isset($body['scope']['fingerprint']) &&
                   $body['scope']['fingerprint'] === 'site-fingerprint-123';
        });
    }

    public function test_throws_on_http_error(): void
    {
        Http::fake(fn () => Http::response(json_encode([
            'error' => 'Invalid license key',
        ]), 400));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anystack license validation failed with status 400');

        $this->client->validateLicense('test-product', 'invalid-key');
    }

    public function test_throws_on_server_error(): void
    {
        Http::fake(fn () => Http::response(json_encode([
            'error' => 'Internal server error',
        ]), 500));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anystack license validation failed with status 500');

        $this->client->validateLicense('test-product', 'test-key');
    }

    public function test_composer_repository_url_construction(): void
    {
        $url = $this->client->composerRepositoryUrl('vendor-name');

        $this->assertEquals('https://api.anystack.sh/composer/vendor-name', $url);
    }
}
