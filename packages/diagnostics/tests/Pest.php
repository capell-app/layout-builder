<?php

declare(strict_types=1);

use Capell\Diagnostics\Tests\DiagnosticsTestCase;

pest()->extend(DiagnosticsTestCase::class)->group('diagnostics')->in(__DIR__);
