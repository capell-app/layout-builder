<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins\Unit;

use ArrayObject;
use Capell\Plugins\Filament\Resources\MarketplacePlugin\Schemas\MarketplacePluginForm;
use Capell\Tests\Plugins\PluginsTestCase;

/**
 * Unit coverage for the JSON transformers used by the admin form's
 * capabilities / compatibility textareas. The transformers bridge the
 * string-only Textarea state and the AsArrayObject-cast model columns;
 * without them, saves silently double-encode or break the cast.
 */
final class MarketplacePluginFormHelpersTest extends PluginsTestCase
{
    public function test_decode_passes_arrays_through_unchanged(): void
    {
        $this->assertSame(
            ['admin_pages', 'db_schema_changes'],
            MarketplacePluginForm::decodeJsonInput(['admin_pages', 'db_schema_changes']),
        );
    }

    public function test_decode_unwraps_array_objects(): void
    {
        $input = new ArrayObject(['php' => '^8.2']);

        $this->assertSame(
            ['php' => '^8.2'],
            MarketplacePluginForm::decodeJsonInput($input),
        );
    }

    public function test_decode_parses_valid_json_string(): void
    {
        $this->assertSame(
            ['admin_pages', 'db_schema_changes'],
            MarketplacePluginForm::decodeJsonInput('["admin_pages","db_schema_changes"]'),
        );
    }

    public function test_decode_handles_json_object(): void
    {
        $this->assertSame(
            ['php' => '^8.2', 'laravel' => '^10|^11'],
            MarketplacePluginForm::decodeJsonInput('{"php":"^8.2","laravel":"^10|^11"}'),
        );
    }

    public function test_decode_returns_empty_array_for_empty_string(): void
    {
        $this->assertSame([], MarketplacePluginForm::decodeJsonInput(''));
        $this->assertSame([], MarketplacePluginForm::decodeJsonInput('   '));
    }

    public function test_decode_returns_empty_array_for_null(): void
    {
        $this->assertSame([], MarketplacePluginForm::decodeJsonInput(null));
    }

    public function test_decode_returns_empty_array_for_invalid_json(): void
    {
        $this->assertSame([], MarketplacePluginForm::decodeJsonInput('{not json'));
    }

    public function test_decode_returns_empty_array_for_non_array_json(): void
    {
        $this->assertSame([], MarketplacePluginForm::decodeJsonInput('42'));
        $this->assertSame([], MarketplacePluginForm::decodeJsonInput('"string"'));
    }

    public function test_encode_returns_empty_string_for_null_and_empty(): void
    {
        $this->assertSame('', MarketplacePluginForm::encodeJsonForDisplay(null));
        $this->assertSame('', MarketplacePluginForm::encodeJsonForDisplay(''));
    }

    public function test_encode_passes_strings_through(): void
    {
        $this->assertSame(
            '["already","json"]',
            MarketplacePluginForm::encodeJsonForDisplay('["already","json"]'),
        );
    }

    public function test_encode_pretty_prints_arrays(): void
    {
        $encoded = MarketplacePluginForm::encodeJsonForDisplay(['admin_pages', 'queue_jobs']);

        $this->assertStringContainsString('"admin_pages"', $encoded);
        $this->assertStringContainsString('"queue_jobs"', $encoded);
        $this->assertStringContainsString("\n", $encoded);
    }

    public function test_encode_unwraps_array_objects_before_encoding(): void
    {
        $input = new ArrayObject(['php' => '^8.2']);

        $encoded = MarketplacePluginForm::encodeJsonForDisplay($input);

        $this->assertStringContainsString('"php"', $encoded);
        $this->assertStringContainsString('"^8.2"', $encoded);
    }

    public function test_encode_returns_empty_string_for_unsupported_types(): void
    {
        $this->assertSame('', MarketplacePluginForm::encodeJsonForDisplay(42));
        $this->assertSame('', MarketplacePluginForm::encodeJsonForDisplay(true));
    }

    public function test_round_trip_preserves_data_through_encode_decode(): void
    {
        $original = ['admin_pages', 'db_schema_changes', 'http_outbound:api.openai.com'];

        $encoded = MarketplacePluginForm::encodeJsonForDisplay($original);
        $decoded = MarketplacePluginForm::decodeJsonInput($encoded);

        $this->assertSame($original, $decoded);
    }
}
