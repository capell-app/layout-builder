<?php

declare(strict_types=1);

use Capell\FrontendAuthoring\Tests\FrontendAuthoringTestCase;

pest()->extend(FrontendAuthoringTestCase::class)->group('frontend-authoring')->in(__DIR__);
