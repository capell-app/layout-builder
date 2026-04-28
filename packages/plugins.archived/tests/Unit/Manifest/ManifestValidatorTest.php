<?php

declare(strict_types=1);

namespace Capell\Plugins\Tests\Unit\Manifest;

use Capell\Plugins\Manifest\Exceptions\ManifestValidationException;
use Capell\Plugins\Manifest\ManifestValidator;
use Capell\Plugins\Tests\PluginsTestCase;

final class ManifestValidatorTest extends PluginsTestCase
{
    private ManifestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ManifestValidator;
    }

    public function test_accepts_the_full_plugin_fixture(): void
    {
        $json = $this->loadFixture('manifests/valid-full-plugin.json');
        $result = $this->validator->validate($json);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    public function test_accepts_the_free_widget_fixture(): void
    {
        $json = $this->loadFixture('manifests/valid-free-widget.json');
        $result = $this->validator->validate($json);

        $this->assertTrue($result->isValid);
    }

    public function test_rejects_fixture_with_missing_name(): void
    {
        $json = $this->loadFixture('manifests/invalid-missing-name.json');
        $result = $this->validator->validate($json);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('name', $result->errors[0] ?? '');
    }

    public function test_rejects_fixture_with_bad_semver(): void
    {
        $json = $this->loadFixture('manifests/invalid-bad-semver.json');
        $result = $this->validator->validate($json);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('version', implode(' ', $result->errors));
    }

    public function test_rejects_fixture_with_unknown_capability(): void
    {
        $json = $this->loadFixture('manifests/invalid-unknown-capability.json');
        $result = $this->validator->validate($json);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('writes_brains', implode(' ', $result->errors));
    }

    public function test_validate_or_fail_throws_on_invalid_input(): void
    {
        $json = $this->loadFixture('manifests/invalid-missing-name.json');

        $this->expectException(ManifestValidationException::class);
        $this->validator->validateOrFail($json);
    }

    public function test_hydrate_returns_a_plugin_manifest_data_from_a_valid_manifest(): void
    {
        $json = $this->loadFixture('manifests/valid-full-plugin.json');
        $manifest = $this->validator->hydrate($json);

        $this->assertSame('acme/super-widget', $manifest->name);
    }
}
