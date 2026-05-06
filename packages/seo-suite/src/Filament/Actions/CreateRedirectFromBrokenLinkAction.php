<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Actions;

use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Redirects\Actions\BuildRedirectCreateUrlAction;
use Capell\Redirects\Actions\ValidateRedirectAction;
use Capell\Redirects\Filament\Resources\Redirects\RedirectResource;
use Capell\SeoSuite\Models\BrokenLink;
use Filament\Actions\Action;

class CreateRedirectFromBrokenLinkAction extends Action
{
    private const BUILD_REDIRECT_CREATE_URL_ACTION = BuildRedirectCreateUrlAction::class;

    private const REDIRECT_RESOURCE = RedirectResource::class;

    private const VALIDATE_REDIRECT_ACTION = ValidateRedirectAction::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('create_redirect')
            ->label(__('redirects::generic.redirect'))
            ->icon('heroicon-o-arrow-uturn-right')
            ->visible(fn (): bool => $this->redirectsAreInstalled())
            ->disabled(fn (): bool => ! $this->redirectsAreInstalled())
            ->url(fn (BrokenLink $record): string => $this->redirectCreateUrl($record));
    }

    private function redirectsAreInstalled(): bool
    {
        return class_exists(self::BUILD_REDIRECT_CREATE_URL_ACTION)
            && class_exists(self::VALIDATE_REDIRECT_ACTION)
            && class_exists(self::REDIRECT_RESOURCE)
            && method_exists(self::REDIRECT_RESOURCE, 'getUrl');
    }

    private function redirectCreateUrl(BrokenLink $brokenLink): string
    {
        $brokenLink->loadMissing(['page.pageUrls', 'page.translations']);

        $page = $brokenLink->page;
        $languageId = $page?->pageUrls->first()?->language_id
            ?? $page?->translations->first()?->language_id;

        return BuildRedirectCreateUrlAction::run(
            sourceUrl: $this->sourceUrl($brokenLink),
            targetUrl: null,
            siteId: $page?->site_id,
            languageId: $languageId,
            statusCode: RedirectStatusCodeEnum::Permanent,
        );
    }

    private function sourceUrl(BrokenLink $brokenLink): ?string
    {
        $targetUrl = trim($brokenLink->target_url);

        if ($targetUrl === '') {
            return null;
        }

        if (str_starts_with($targetUrl, '/') && ! str_starts_with($targetUrl, '//')) {
            return $targetUrl;
        }

        $path = parse_url($targetUrl, PHP_URL_PATH);

        return is_string($path) && str_starts_with($path, '/') && ! str_starts_with($path, '//')
            ? $path
            : null;
    }
}
