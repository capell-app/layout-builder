<?php

declare(strict_types=1);

namespace Capell\Plugins\Tests\Unit;

use Capell\Plugins\Support\SiteIdResolver;
use Capell\Plugins\Tests\PluginsTestCase;
use RuntimeException;

final class SiteIdResolverTest extends PluginsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SiteIdResolver::flushCache();
    }

    protected function tearDown(): void
    {
        SiteIdResolver::flushCache();
        parent::tearDown();
    }

    public function test_returns_stable_hash_across_calls(): void
    {
        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        config()->set('app.name', 'Capell Test');

        $first = SiteIdResolver::get();
        $second = SiteIdResolver::get();

        $this->assertSame($first, $second);
        $this->assertSame(64, strlen($first), 'sha256 hex should be 64 chars');
    }

    public function test_differs_when_app_key_changes(): void
    {
        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        config()->set('app.name', 'Capell Test');

        $first = SiteIdResolver::get();

        SiteIdResolver::flushCache();
        config()->set('app.key', 'base64:' . base64_encode(str_repeat('b', 32)));
        $second = SiteIdResolver::get();

        $this->assertNotSame($first, $second);
    }

    public function test_throws_when_app_key_missing(): void
    {
        config()->set('app.key', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_KEY is not configured');

        SiteIdResolver::get();
    }
}
