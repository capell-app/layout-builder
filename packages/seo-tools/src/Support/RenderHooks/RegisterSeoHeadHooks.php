<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\RenderHooks;

use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Enums\RenderHookScenario;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\SeoTools\Actions\BuildSocialMetaAction;

class RegisterSeoHeadHooks
{
    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        $this->registry->register(
            RenderHookLocation::HeadClose,
            function (RenderHookContext $context): string {
                $item = is_array($context->item) ? $context->item : [];
                $page = $item['page'] ?? null;
                $site = $item['site'] ?? null;
                $language = $item['language'] ?? null;

                if ($page === null || $site === null || $language === null) {
                    return '';
                }

                $meta = BuildSocialMetaAction::run($page, $site, $language);

                return view('capell::head.social-meta', [
                    'meta' => $meta,
                    'page' => $page,
                    'site' => $site,
                    'language' => $language,
                ])->render();
            },
            scenario: RenderHookScenario::SeoMeta->value,
        );
    }
}
