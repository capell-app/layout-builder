<?php

declare(strict_types=1);

use Capell\WordPressImporter\Tests\WordPressImporterTestCase;

pest()->extend(WordPressImporterTestCase::class)->group('wordpress-importer')->in(__DIR__);
