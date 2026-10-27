<?php

declare(strict_types=1);

use PHPUnit\Framework\ExpectationFailedException;

it('keeps dark inspector section headings readable and quieter than body values', function (): void {
    $stylesheet = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/css/layout-builder/admin/capell-layout-filament.css');

    layoutBuilderAssertInspectorContrast($stylesheet);
});

function layoutBuilderAssertInspectorContrast(string $stylesheet): void
{
    $declaredColour = function (string $selector, string $property) use ($stylesheet): string {
        $matched = preg_match(
            '/(?:^|[},])\s*' . preg_quote($selector, '/') . '\s*\{[^}]*?\b' . $property . '\s*:\s*([^;}]+)/m',
            $stylesheet,
            $matches,
        );

        expect($matched)->toBe(1, "Expected {$selector} to declare {$property} as a colour.");

        return trim($matches[1]);
    };

    $luminance = function (string $colour): float {
        $colour = layoutBuilderInspectorRgb($colour);
        $channels = array_map(function (float $channel): float {
            $srgb = $channel / 255;

            return $srgb <= 0.04045 ? $srgb / 12.92 : (($srgb + 0.055) / 1.055) ** 2.4;
        }, $colour);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    };

    $panel = $luminance($declaredColour('.dark .layout-builder-inspector-panel', 'background'));
    $heading = $luminance($declaredColour('.dark .layout-builder-inspector-card h4', 'color'));
    $body = $luminance($declaredColour('.dark .layout-builder-inspector-field dd', 'color'));
    $contrast = (max($heading, $panel) + 0.05) / (min($heading, $panel) + 0.05);

    expect($contrast)->toBeGreaterThanOrEqual(4.5)
        ->and($heading)->toBeLessThan($body)
        ->toBeGreaterThan($panel);
}

/** @return array{float, float, float} */
function layoutBuilderInspectorRgb(string $colour): array
{
    if (preg_match('/^#([0-9a-f]{3,8})$/i', $colour, $hex) === 1) {
        $value = $hex[1];
        if (strlen($value) === 3 || strlen($value) === 4) {
            $value = implode('', array_map(fn (string $digit): string => $digit . $digit, str_split($value)));
        }
        if (strlen($value) === 8) {
            expect(substr($value, 6))->toBe('ff', 'Expected an opaque inspector colour.');
        }
        expect(strlen($value))->toBeIn([6, 8]);

        return [(float) hexdec(substr($value, 0, 2)), (float) hexdec(substr($value, 2, 2)), (float) hexdec(substr($value, 4, 2))];
    }

    if (preg_match('/^rgba?\((.+)\)$/i', $colour, $rgb) !== 1) {
        throw new ExpectationFailedException('Expected a supported CSS colour.');
    }
    $parts = preg_split('/\s*[,\/]\s*|\s+/', trim($rgb[1]));
    if ($parts === false) {
        throw new ExpectationFailedException('Unable to parse CSS colour channels.');
    }
    expect(count($parts))->toBeIn([3, 4]);
    if (isset($parts[3])) {
        expect((float) $parts[3] / (str_ends_with($parts[3], '%') ? 100 : 1))->toBe(1.0, 'Expected an opaque inspector colour.');
    }
    $channel = static function (string $value): float {
        expect(preg_match('/^(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)%?$/', $value))->toBe(1);
        $channel = (float) $value * (str_ends_with($value, '%') ? 255 / 100 : 1);
        expect($channel)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(255);

        return $channel;
    };

    return [$channel($parts[0]), $channel($parts[1]), $channel($parts[2])];
}

it('accepts equivalent inspector heading colour notations', function (string $colour): void {
    $stylesheet = ".dark .layout-builder-inspector-panel { background: #111827; }
        .dark .layout-builder-inspector-card h4 { color: {$colour}; }
        .dark .layout-builder-inspector-field dd { color: #f1f5f9; }";

    layoutBuilderAssertInspectorContrast($stylesheet);
})->with([
    'hex' => '#94a3b8',
    'hex alpha' => '#94a3b8ff',
    'comma RGB' => 'rgb(148, 163, 184)',
    'space RGB' => 'rgb(148 163 184)',
    'RGBA' => 'rgba(148, 163, 184, 1)',
    'modern alpha' => 'rgb(148 163 184 / 100%)',
    'percent RGB' => 'rgb(58.03921568627451% 63.92156862745098% 72.15686274509804%)',
]);

it('rejects a missing heading rule or insufficient contrast', function (string $heading, string $message): void {
    $stylesheet = ".dark .layout-builder-inspector-panel { background: #111827; }
        {$heading}
        .dark .layout-builder-inspector-field dd { color: #f1f5f9; }";

    expect(fn () => layoutBuilderAssertInspectorContrast($stylesheet))
        ->toThrow(ExpectationFailedException::class, $message);
})->with([
    'missing rule' => ['', 'Expected .dark .layout-builder-inspector-card h4 to declare color as a colour.'],
    'below 4.5:1' => ['.dark .layout-builder-inspector-card h4 { color: #64748b; }', 'is equal to 4.5 or is greater than 4.5'],
]);
