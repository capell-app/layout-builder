<?php

declare(strict_types=1);

use Capell\Tests\Blog\BlogTestCase;
use Capell\Tests\Layout\LayoutTestCase;
use Capell\Tests\packages\ArchTestCase;

pest()->extends(ArchTestCase::class)->in(__DIR__.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'PackagesTest.php');
pest()->extends(BlogTestCase::class)->in(__DIR__.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'blog');
pest()->extends(LayoutTestCase::class)->in(__DIR__.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'layout');
