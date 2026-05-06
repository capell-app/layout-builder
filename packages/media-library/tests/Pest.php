<?php

declare(strict_types=1);

use Capell\MediaLibrary\Tests\MediaLibraryTestCase;

pest()->extend(MediaLibraryTestCase::class)->group('media-library')->in(__DIR__);
