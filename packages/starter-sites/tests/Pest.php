<?php

declare(strict_types=1);

use Capell\StarterSites\Tests\StarterSitesTestCase;

pest()->extend(StarterSitesTestCase::class)->group('starter-sites')->in(__DIR__);
