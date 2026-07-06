<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Tests\LayoutBuilderTestCase;

require_once __DIR__ . '/LayoutBuilderTestCase.php';

pest()->extend(LayoutBuilderTestCase::class)->group('layout-builder')->in('.');
