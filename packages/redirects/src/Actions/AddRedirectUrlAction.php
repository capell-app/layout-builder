<?php

declare(strict_types=1);

namespace Capell\Redirects\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Pageable $page, Language $language, string $url)
 */
class AddRedirectUrlAction
{
    use AsObject;

    public function handle(Pageable $page, Language $language, string $url): void
    {
        throw_unless($this->isValidRedirectUrl($url), InvalidArgumentException::class, sprintf("Invalid redirect url: '%s'. It must start with '/' and contain only URL-safe characters.", $url));
        throw_unless($this->siteSupportsLanguage($page, $language), InvalidArgumentException::class, sprintf("Language '%s' is not configured for site ID %d.", $language->getKey(), $page->site_id));

        $url = $page->pageUrls()->firstOrCreate(
            [
                'language_id' => $language->id,
                'site_id' => $page->site_id,
                'url' => $url,
                'type' => UrlTypeEnum::Redirect,
            ],
            [
                'is_manual' => true,
            ],
        );

        $url->deleteCache();
    }

    private function isValidRedirectUrl(string $url): bool
    {
        if ($url === '' || $url[0] !== '/') {
            return false;
        }

        // allow path-only URLs with common safe characters
        return (bool) preg_match('/^\/[A-Za-z0-9._~\-\/]*$/', $url);
    }

    private function siteSupportsLanguage(Pageable $page, Language $language): bool
    {
        return $page->site()
            ->where(function (Builder $query) use ($language): void {
                $query
                    ->where('language_id', $language->getKey())
                    ->orWhereHas('siteDomains', fn (Builder $query): Builder => $query->where('language_id', $language->getKey()));
            })
            ->exists();
    }
}
