<?php

declare(strict_types=1);

use Capell\SeoSuite\Tests\SeoSuiteTestCase;

pest()->extend(SeoSuiteTestCase::class)->group('seo-suite')->in(__DIR__);
