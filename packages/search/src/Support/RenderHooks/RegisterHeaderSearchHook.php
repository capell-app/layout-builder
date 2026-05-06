<?php

declare(strict_types=1);

namespace Capell\Search\Support\RenderHooks;

use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Search\Actions\ResolveSearchSettingAction;

final class RegisterHeaderSearchHook
{
    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        if (! (bool) ResolveSearchSettingAction::run(
            'enabled',
            'capell-search.enabled',
            true,
        )) {
            return;
        }

        if (! (bool) ResolveSearchSettingAction::run(
            'show_header_search',
            'capell-search.show_header_search',
            true,
        )) {
            return;
        }

        $this->registry->register(
            RenderHookLocation::HeaderAfter,
            static fn (): string => view('capell-search::components.form')->render(),
        );
    }
}
