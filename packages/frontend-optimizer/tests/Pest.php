<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Tests\FrontendOptimizerTestCase;

pest()->extend(FrontendOptimizerTestCase::class)->group('frontend-optimizer')->in(__DIR__);
