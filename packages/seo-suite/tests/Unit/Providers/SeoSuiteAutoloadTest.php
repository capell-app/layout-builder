<?php

declare(strict_types=1);

use Capell\SeoSuite\Handlers\ClearCircuitBreakerHandler;
use Capell\SeoSuite\Targets\FlatJsonTarget;

it('autoloads seo tools provider dependencies from their PSR-4 paths', function (): void {
    expect(class_exists(FlatJsonTarget::class))->toBeTrue()
        ->and(class_exists(ClearCircuitBreakerHandler::class))->toBeTrue();
});
