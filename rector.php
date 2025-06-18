<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/packages',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        rectorPreset: true,
    )
    ->withTypeCoverageLevel(2)
    ->withDeadCodeLevel(2)
    ->withCodeQualityLevel(2)
    ->withPhpSets(php84: true);
