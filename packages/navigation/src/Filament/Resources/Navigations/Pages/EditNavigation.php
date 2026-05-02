<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Navigations\Pages;

use Capell\Admin\Contracts\PageCacheNotifiable;
use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Admin\Filament\Actions\DeleteAction;
use Capell\Admin\Filament\Actions\ReplicateAction;
use Capell\Admin\Filament\Concerns\HasCreateActionOnEditPage;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Filament\Actions\ActionGroup;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Howdu\FilamentRecordSwitcher\Filament\Concerns\HasRecordSwitcher;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Override;

/**
 * @property-read Navigation $record
 */
class EditNavigation extends EditRecord implements PageCacheNotifiable
{
    use HasCreateActionOnEditPage;
    use HasPageCacheNotification;
    use HasRecordSwitcher{
        afterSave as recordSwitcherAfterSave;
    }

    /** @return class-string<NavigationResource> */
    #[Override]
    public static function getResource(): string
    {
        return NavigationResource::class;
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return new HtmlString(
            __('capell-admin::heading.edit_navigation_record', [
                'name' => Str::limit($this->record->name, 40),
                'site' => Str::limit($this->record->site->name, 40),
            ]),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            RestoreAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            ActionGroup::make([
                CreateAction::make()
                    ->groupedIcon('heroicon-o-plus-circle')
                    ->slideOver(),
                ReplicateAction::make()
                    ->hidden($this->record->trashed()),
            ]),
        ];
    }

    protected function afterSave(): void
    {
        $this->notifyPageCached($this->record);

        $this->recordSwitcherAfterSave();
    }
}
