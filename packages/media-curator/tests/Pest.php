<?php

declare(strict_types=1);

use Capell\MediaCurator\Tests\MediaCuratorTestCase;

pest()->extend(MediaCuratorTestCase::class)->in('Integration', 'Feature');
