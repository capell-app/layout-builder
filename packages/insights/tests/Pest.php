<?php

declare(strict_types=1);

use Capell\Insights\Tests\InsightsTestCase;

pest()->extend(InsightsTestCase::class)->group('insights')->in(__DIR__);
