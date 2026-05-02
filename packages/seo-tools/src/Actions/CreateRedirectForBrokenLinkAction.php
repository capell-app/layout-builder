<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\Redirects\Actions\ValidateRedirectAction;
use Capell\SeoTools\Models\BrokenLink;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static PageUrl run(BrokenLink $brokenLink, string $sourceUrl, string $targetUrl, RedirectStatusCodeEnum $statusCode, ?string $notes = null)
 */
final class CreateRedirectForBrokenLinkAction
{
    use AsAction;

    public function handle(
        BrokenLink $brokenLink,
        string $sourceUrl,
        string $targetUrl,
        RedirectStatusCodeEnum $statusCode,
        ?string $notes = null,
    ): PageUrl {
        $brokenLink->loadMissing(['page.translations', 'page.pageUrls']);

        $page = $brokenLink->page;
        $siteId = $page?->site_id;
        $languageId = $page?->pageUrls->first()?->language_id
            ?? $page?->translations->first()?->language_id;

        if ($siteId === null || $languageId === null) {
            throw ValidationException::withMessages([
                'source_url' => __('capell-seo-tools::generic.redirect_create_missing_context'),
            ]);
        }

        $result = ValidateRedirectAction::run(
            sourceUrl: $sourceUrl,
            targetUrl: $targetUrl,
            siteId: (int) $siteId,
            languageId: (int) $languageId,
            statusCode: $statusCode->value,
        );

        if ($result['errors'] !== []) {
            throw ValidationException::withMessages([
                'target_url' => $result['errors'],
            ]);
        }

        return PageUrl::query()->create([
            'site_id' => $siteId,
            'language_id' => $languageId,
            'url' => $sourceUrl,
            'target_url' => $targetUrl,
            'status_code' => $statusCode,
            'type' => UrlTypeEnum::Redirect,
            'is_manual' => true,
            'status' => true,
            'notes' => $notes,
        ]);
    }
}
