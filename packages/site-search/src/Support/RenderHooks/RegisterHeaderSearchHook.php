<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Support\RenderHooks;

use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\SiteSearch\Actions\ResolveSiteSearchSettingAction;

final class RegisterHeaderSearchHook
{
    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        if (! (bool) ResolveSiteSearchSettingAction::run(
            'enabled',
            'capell-site-search.enabled',
            true,
        )) {
            return;
        }

        if (! (bool) ResolveSiteSearchSettingAction::run(
            'show_header_search',
            'capell-site-search.show_header_search',
            true,
        )) {
            return;
        }

        $this->registry->register(
            RenderHookLocation::HeaderAfter,
            static fn (): string => view('capell-site-search::components.form')->render(),
        );
    }
}
