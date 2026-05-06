<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Filament\Widgets\Health;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Contracts\Dashboard\ContentHealthDataProvider;
use Capell\Admin\Data\Dashboard\ContentHealthData;
use Capell\Admin\Data\Dashboard\ContentHealthIssueData;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Diagnostics\Actions\Dashboard\BuildSetupHealthAction;
use Capell\Diagnostics\Data\Dashboard\SetupHealthData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class SiteHealthWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = [];

    protected static string $settingsKey = 'site_health';

    protected string $view = 'capell-diagnostics::widgets.site-health';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 6;

    #[Computed(persist: true, seconds: 300)]
    public function setupHealth(): SetupHealthData
    {
        return BuildSetupHealthAction::run();
    }

    #[Computed(persist: true, seconds: 300)]
    public function contentHealth(): ContentHealthData
    {
        return resolve(ContentHealthDataProvider::class)->build();
    }

    #[Computed]
    public function allGood(): bool
    {
        return $this->setupHealth->allGreen && $this->contentHealth->issues->toCollection()->every(fn (ContentHealthIssueData $issue): bool => $issue->count === 0);
    }
}
