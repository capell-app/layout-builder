<?php

declare(strict_types=1);

use Capell\ContentBlocks\Tests\BlockLibraryTestCase;

require_once __DIR__ . '/BlockLibraryTestCase.php';

pest()->extend(BlockLibraryTestCase::class)->group('block-library')->in(__DIR__);
