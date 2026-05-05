<?php

declare(strict_types=1);

use Capell\ExampleSites\Tests\ExampleSitesTestCase;

pest()->extend(ExampleSitesTestCase::class)->group('example-sites')->in(__DIR__);
