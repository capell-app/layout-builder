<?php

declare(strict_types=1);

use Capell\PublishingStudio\Tests\PublishingStudioTestCase;

pest()->extend(PublishingStudioTestCase::class)->group('publishing-studio')->in(__DIR__);
