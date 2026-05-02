<?php

declare(strict_types=1);

namespace Capell\Mcp\Tools\Site;

use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

#[Name('capell-site-inspect-state')]
#[Title('Inspect Site State')]
#[Description('Inspect high-level installed Capell app state without exposing private content bodies.')]
#[IsReadOnly]
final class InspectSiteStateTool extends Tool
{
    public function handle(): ResponseFactory
    {
        return Response::structured([
            'app' => [
                'name' => config('app.name'),
                'environment' => app()->environment(),
                'debug' => (bool) config('app.debug'),
            ],
            'packages' => $this->installedCapellPackages(),
            'counts' => $this->modelCounts(),
        ]);
    }

    /** @return array<string, string|null> */
    private function installedCapellPackages(): array
    {
        $packages = [];

        foreach (InstalledVersions::getInstalledPackages() as $packageName) {
            if (! str_starts_with($packageName, 'capell-app/')) {
                continue;
            }

            $packages[$packageName] = InstalledVersions::getPrettyVersion($packageName);
        }

        ksort($packages);

        return $packages;
    }

    /** @return array<string, int|null> */
    private function modelCounts(): array
    {
        return [
            'sites' => $this->countOptionalModel('Capell\\Core\\Models\\Site'),
            'languages' => $this->countOptionalModel('Capell\\Core\\Models\\Language'),
            'pages' => $this->countOptionalModel('Capell\\Core\\Models\\Page'),
            'pageUrls' => $this->countOptionalModel('Capell\\Core\\Models\\PageUrl'),
            'types' => $this->countOptionalModel('Capell\\Core\\Models\\Type'),
            'redirects' => $this->countOptionalModel('Capell\\Redirects\\Models\\Redirect'),
            'navigations' => $this->countOptionalModel('Capell\\Navigation\\Models\\Navigation'),
        ];
    }

    private function countOptionalModel(string $modelClass): ?int
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        return $this->countModel($modelClass);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function countModel(string $modelClass): ?int
    {
        try {
            return $modelClass::query()->count();
        } catch (Throwable) {
            return null;
        }
    }
}
