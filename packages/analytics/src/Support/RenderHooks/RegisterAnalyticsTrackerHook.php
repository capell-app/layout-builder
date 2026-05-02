<?php

declare(strict_types=1);

namespace Capell\Analytics\Support\RenderHooks;

use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;

class RegisterAnalyticsTrackerHook
{
    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        $this->registry->register(
            RenderHookLocation::BodyEnd,
            static fn (): string => view('capell-analytics::tracker')->render(),
        );
    }
}
