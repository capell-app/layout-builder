<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Widgets\Health;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Contracts\Dashboard\ContentHealthDataProvider;
use Capell\Admin\Data\Dashboard\ContentHealthData;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class ContentHealthWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['editor', 'admin', 'super_admin'];

    protected static string $settingsKey = 'content_health';

    protected string $view = 'capell-developer-tools::widgets.content-health';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    public static function getDescription(): string
    {
        return (string) __('capell-admin::dashboard.widget_content_health_description');
    }

    #[Computed(persist: true, seconds: 300)]
    public function data(): ContentHealthData
    {
        return resolve(ContentHealthDataProvider::class)->build();
    }
}
