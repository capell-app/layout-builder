<?php

declare(strict_types=1);

use Capell\Mosaic\Tests\MosaicTestCase;

pest()->extend(MosaicTestCase::class)->group('mosaic')->in(__DIR__);
