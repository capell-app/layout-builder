<?php

declare(strict_types=1);

use Capell\Tests\Address\AddressTestCase;
use Capell\Tests\Assistant\AssistantTestCase;
use Capell\Tests\Blog\BlogTestCase;
use Capell\Tests\Mosaic\MosaicTestCase;
use Capell\Tests\Packages\PackagesTestCase;

$testsRoot = __DIR__ . DIRECTORY_SEPARATOR . 'src';

pest()->extends(PackagesTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Packages');

pest()->extends(AddressTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Address');

pest()->extends(BlogTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Blog');

pest()->extends(MosaicTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Mosaic');

pest()->extends(AssistantTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Assistant');
