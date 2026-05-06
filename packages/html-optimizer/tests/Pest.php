<?php

declare(strict_types=1);

use Capell\HtmlOptimizer\Tests\HtmlOptimizerTestCase;

pest()->extend(HtmlOptimizerTestCase::class)->group('html-optimizer')->in(__DIR__);
