<?php

declare(strict_types=1);

use Capell\AIOrchestrator\Tests\AIOrchestratorTestCase;

pest()->extend(AIOrchestratorTestCase::class)->group('ai-orchestrator')->in(__DIR__);
