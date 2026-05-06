<?php

declare(strict_types=1);

use Capell\BlockLibrary\Tests\ContentBlockRenderingTestCase;

pest()->extend(ContentBlockRenderingTestCase::class)->group('block-library')->in(__DIR__ . '/Unit');
pest()->extend(ContentBlockRenderingTestCase::class)->group('block-library')->in(__DIR__ . '/Feature');
