<?php

declare(strict_types=1);

use Capell\DemoKit\Tests\DemoKitTestCase;

pest()->extend(DemoKitTestCase::class)->group('demo-kit')->in(__DIR__);
