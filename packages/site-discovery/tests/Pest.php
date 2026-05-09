<?php

declare(strict_types=1);

use Capell\SiteDiscovery\Tests\SiteDiscoveryTestCase;

pest()->extend(SiteDiscoveryTestCase::class)->group('site-discovery')->in(__DIR__);
