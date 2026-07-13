<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Tests\LayoutBuilderTestCase;

require_once __DIR__ . '/LayoutBuilderTestCase.php';

pest()->extend(LayoutBuilderTestCase::class)->group('layout-builder')->in('.');

function layoutBuilderTestInteger(mixed $value): int
{
    if (! is_numeric($value)) {
        throw new RuntimeException('Expected a numeric test value.');
    }

    return (int) $value;
}
