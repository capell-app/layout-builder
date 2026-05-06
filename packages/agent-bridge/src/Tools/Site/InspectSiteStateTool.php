<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Tools\Site;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Navigation\Models\Navigation;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Model;
use Laravel\AgentBridge\Response;
use Laravel\AgentBridge\ResponseFactory;
use Laravel\AgentBridge\Server\Attributes\Description;
use Laravel\AgentBridge\Server\Attributes\Name;
use Laravel\AgentBridge\Server\Attributes\Title;
use Laravel\AgentBridge\Server\Tool;
use Laravel\AgentBridge\Server\Tools\Annotations\IsReadOnly;
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
            'sites' => $this->countOptionalModel(Site::class),
            'languages' => $this->countOptionalModel(Language::class),
            'pages' => $this->countOptionalModel(Page::class),
            'pageUrls' => $this->countOptionalModel(PageUrl::class),
            'types' => $this->countOptionalModel(Type::class),
            'redirects' => $this->countOptionalModel('Capell\\Redirects\\Models\\Redirect'),
            'navigations' => $this->countOptionalModel(Navigation::class),
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
