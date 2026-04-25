<?php

declare(strict_types=1);

namespace Capell\Toolbar\Http\Controllers;

use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Actions\GetUrlCachePathAction;
use Capell\Core\Actions\LoadSiteDomainFromUrlAction;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Support\Cache\PageCacheService;
use Capell\Frontend\Contracts\AdminAccessCheckerInterface;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\Loader\SiteLoader;
use Capell\Toolbar\Http\Requests\BeaconRequest;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Date;

class BeaconController extends BaseController
{
    public function __invoke(BeaconRequest $request): JsonResponse
    {
        $data = [
            'csrf_token' => csrf_token(),
        ];

        [$siteDomain, $url] = LoadSiteDomainFromUrlAction::run($request->url, sites: SiteLoader::getSites());

        if (! $siteDomain) {
            return response()->json([
                'message' => 'Not Found',
            ], 404);
        }

        $pageUrl = null;

        PageUrl::withoutEvents(function () use ($siteDomain, $url, &$pageUrl): void {
            $pageUrl = PageLoader::getPageUrl(
                site: $siteDomain->site,
                language: $siteDomain->language,
                url: $url,
                withEvents: false,
            );

            if (! $pageUrl instanceof PageUrl) {
                $pageUrl = PageLoader::getWildCardUrl(
                    site: $siteDomain->site,
                    language: $siteDomain->language,
                    url: $url,
                    withEvents: false,
                );
            }
        });

        if ($request->user() !== null) {
            /** @var User $user */
            $user = $request->user();

            $data['user'] = [
                'id' => $user->getKey(),
                'name' => (string) data_get($user, 'name'),
            ];

            if ($this->isAdminUser($user) && config('capell-frontend-toolbar.enabled') === true) {
                $data['user']['admin'] = true;

                if ($pageUrl instanceof PageUrl) {
                    $data['editor_html'] = view('capell::components.toolbar', [
                        'pageUrl' => $pageUrl,
                        'htmlCache' => $this->htmlCache($url, $siteDomain),
                        'editUrl' => GetEditPageResourceUrlAction::run($pageUrl->pageable),
                    ])
                        ->render();
                }
            }
        }

        return response()->json($data);
    }

    protected function htmlCache(string $url, SiteDomain $siteDomain): ?array
    {
        $file = GetUrlCachePathAction::run($url, $siteDomain);

        if (in_array($file, [null, '', '0'], true)) {
            return null;
        }

        $file = str_replace(['../', '..\\'], '', $file);
        $pageCache = resolve(PageCacheService::class);
        $filePath = $pageCache->path($file);
        $time = null;

        if ($filePath !== null && $pageCache->exists($file)) {
            $modified = $pageCache->lastModified($file);
            $time = Date::createFromTimestamp($modified, 'UTC')->setTimezone(config('app.timezone'));
        }

        return [
            'cacheTime' => $time ?? null,
            'deleteUrl' => route('capell-frontend.cache.delete', ['file' => base64_encode($file)]),
        ];
    }

    private function isAdminUser(AuthenticatableContract $user): bool
    {
        $checker = resolve(AdminAccessCheckerInterface::class);

        return $checker->isAdmin($user);
    }
}
