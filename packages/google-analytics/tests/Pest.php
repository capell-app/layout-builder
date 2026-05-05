<?php

declare(strict_types=1);

use Capell\GoogleAnalytics\Tests\GoogleAnalyticsTestCase;

pest()->extend(GoogleAnalyticsTestCase::class)->group('google-analytics')->in(__DIR__);
