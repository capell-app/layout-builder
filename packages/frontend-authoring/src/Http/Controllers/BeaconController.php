<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Http\Controllers;

use Capell\Core\Actions\LoadSiteDomainFromUrlAction;
use Capell\Core\Models\PageUrl;
use Capell\Frontend\Contracts\AdminAccessCheckerInterface;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\Loader\SiteLoader;
use Capell\FrontendAuthoring\Actions\BuildEditableRegionManifestAction;
use Capell\FrontendAuthoring\Http\Requests\BeaconRequest;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

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

            if ($this->isAdminUser($user) && config('capell-frontend-authoring.enabled') === true) {
                $data['user']['admin'] = true;

                if ($pageUrl instanceof PageUrl) {
                    $data['scripts'] = [
                        view('capell::authoring.bootstrap-script', [
                            'regions' => BuildEditableRegionManifestAction::run($pageUrl),
                        ])->render(),
                    ];
                }
            }
        }

        return response()->json($data);
    }

    private function isAdminUser(AuthenticatableContract $user): bool
    {
        $checker = resolve(AdminAccessCheckerInterface::class);

        return $checker->isAdmin($user);
    }
}
