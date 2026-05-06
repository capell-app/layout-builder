<?php

declare(strict_types=1);

use Capell\AdminPreview\Tests\AdminPreviewTestCase;

pest()->extend(AdminPreviewTestCase::class)->group('admin-preview')->in(__DIR__);
