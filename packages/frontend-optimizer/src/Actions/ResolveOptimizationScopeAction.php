<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Actions;

use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Lorisleiva\Actions\Concerns\AsAction;

class ResolveOptimizationScopeAction
{
    use AsAction;

    public function handle(?OptimizationScope $layoutScope = null, ?OptimizationScope $siteScope = null): OptimizationScope
    {
        if ($layoutScope instanceof OptimizationScope) {
            return $layoutScope;
        }

        if ($siteScope instanceof OptimizationScope) {
            return $siteScope;
        }

        $configuredScope = config('capell-frontend-optimizer.scope', OptimizationScope::Layout->value);

        return is_string($configuredScope)
            ? OptimizationScope::tryFrom($configuredScope) ?? OptimizationScope::Layout
            : OptimizationScope::Layout;
    }
}
