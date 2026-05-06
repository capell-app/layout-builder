<?php

declare(strict_types=1);

namespace Capell\Insights\Support\RenderHooks;

use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;

class RegisterInsightsTrackerHook
{
    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        $this->registry->register(
            RenderHookLocation::BodyEnd,
            static fn (): string => view('capell-insights::tracker')->render(),
        );
    }
}
