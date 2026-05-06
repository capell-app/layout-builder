<?php

declare(strict_types=1);

use Capell\Search\Tests\SearchTestCase;

pest()->extend(SearchTestCase::class)->group('search')->in(__DIR__);
