<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Filament\Widgets\Health;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Admin\Settings\AdminSettings;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Site;
use Capell\Diagnostics\Actions\Dashboard\BuildCacheHealthAction;
use Capell\Diagnostics\Data\Dashboard\CacheHealthData;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

final class CacheHealthWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    private const WARM_SITE_CACHE_ACTION = 'Capell\\Admin\\Actions\\Cache\\WarmSiteCacheAction';

    public ?int $selectedSiteId = null;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'cache_health';

    protected string $view = 'capell-diagnostics::widgets.cache-health';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    public static function getDescription(): string
    {
        return (string) __('capell-admin::dashboard.widget_cache_health_description');
    }

    public function mount(): void
    {
        $this->selectedSiteId = $this->siteQuery()->value('id');
    }

    public function getPollingInterval(): string
    {
        $seconds = resolve(AdminSettings::class)->cache_health_refresh_interval_seconds;

        return $seconds . 's';
    }

    #[Computed]
    public function data(): ?CacheHealthData
    {
        if ($this->selectedSiteId === null) {
            return null;
        }

        $site = $this->siteQuery()->find($this->selectedSiteId);

        if (! $site instanceof Site) {
            return null;
        }

        return BuildCacheHealthAction::run($site);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    #[Computed]
    public function sites(): array
    {
        return $this->siteQuery()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Site $site): array => ['id' => $site->id, 'name' => $site->name])
            ->all();
    }

    public function warmCache(): void
    {
        if ($this->selectedSiteId === null) {
            return;
        }

        $site = $this->siteQuery()->find($this->selectedSiteId);

        if (! $site instanceof Site) {
            return;
        }

        $action = self::WARM_SITE_CACHE_ACTION;

        if (class_exists($action)) {
            $action::run($site);
        }

        $this->dispatch('$refresh');
    }

    private function siteQuery(): Builder
    {
        return SiteScope::applyForCurrentActor(Site::query(), 'id');
    }
}
