<?php

declare(strict_types=1);

use Capell\GA4Reports\Tests\GA4ReportsTestCase;

pest()->extend(GA4ReportsTestCase::class)->group('ga4-reports')->in(__DIR__);
