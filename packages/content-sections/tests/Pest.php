<?php

declare(strict_types=1);

use Capell\ContentSections\Tests\ContentSectionsTestCase;

pest()->extend(ContentSectionsTestCase::class)->group('content-sections')->in(__DIR__);
