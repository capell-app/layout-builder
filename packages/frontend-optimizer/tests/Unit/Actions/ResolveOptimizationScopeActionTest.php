<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Actions\ResolveOptimizationScopeAction;
use Capell\FrontendOptimizer\Enums\OptimizationScope;

it('resolves optimization scope from layout then site then config default', function (): void {
    config()->set('capell-frontend-optimizer.scope', OptimizationScope::RenderProfile->value);

    expect(ResolveOptimizationScopeAction::run(OptimizationScope::PageUrl, OptimizationScope::Layout))
        ->toBe(OptimizationScope::PageUrl)
        ->and(ResolveOptimizationScopeAction::run(null, OptimizationScope::Layout))->toBe(OptimizationScope::Layout)
        ->and(ResolveOptimizationScopeAction::run())->toBe(OptimizationScope::RenderProfile);
});

it('falls back to layout scope when configured scope is invalid', function (): void {
    config()->set('capell-frontend-optimizer.scope', 'invalid');

    expect(ResolveOptimizationScopeAction::run())->toBe(OptimizationScope::Layout);
});
