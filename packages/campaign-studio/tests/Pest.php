<?php

declare(strict_types=1);

use Capell\CampaignStudio\Tests\CampaignStudioTestCase;

pest()->extend(CampaignStudioTestCase::class)->group('campaign-studio')->in(__DIR__);
