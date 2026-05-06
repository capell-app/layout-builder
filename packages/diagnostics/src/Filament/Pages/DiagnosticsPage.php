<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Filament\Pages;

use BackedEnum;
use Capell\Admin\Contracts\RegistryInspectorInterface;
use Capell\Admin\Filament\Actions\Makers\RunMakerFilamentAction;
use Capell\Core\Contracts\Makers\Maker;
use Capell\Core\Contracts\Makers\MakerRegistryInterface;
use Capell\Core\Data\Makers\MakerDefinitionData;
use Capell\Core\Support\Makers\MakerSafety;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Override;

class DiagnosticsPage extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?string $slug = 'diagnostics';

    protected static ?int $navigationSort = 9;

    protected string $view = 'capell-diagnostics::filament.pages.diagnostics';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('capell-admin::navigation.diagnostics');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_administration');
    }

    public static function canAccess(): bool
    {
        return config('capell.dashboard.developer_page_enabled', true)
            && (Gate::allows('accessDiagnostics') || Gate::allows('viewDiagnostics') || auth()->user()?->can('accessDiagnostics') === true);
    }

    public function getTitle(): string|Htmlable
    {
        return __('capell-admin::generic.diagnostics');
    }

    public function makers(): Collection
    {
        return resolve(MakerRegistryInterface::class)->all()
            ->map(fn (Maker $maker): MakerDefinitionData => $maker->definition());
    }

    public function safety(): array
    {
        return resolve(MakerSafety::class)->current()->toArray();
    }

    public function configurators(): Collection
    {
        return resolve(RegistryInspectorInterface::class)->configurators();
    }

    public function components(): Collection
    {
        return resolve(RegistryInspectorInterface::class)->components();
    }

    public function blocks(): Collection
    {
        return resolve(RegistryInspectorInterface::class)->blocks();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return $this->makers()
            ->map(fn (MakerDefinitionData $maker): Action => RunMakerFilamentAction::make($maker->key))
            ->values()
            ->all();
    }
}
