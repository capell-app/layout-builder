<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Sections\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\DeleteAction;
use Capell\Admin\Filament\Actions\ReplicateAction;
use Capell\Admin\Filament\Concerns\HasAncestorBreadcrumbs;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Admin\Filament\Concerns\HasTypeRelationManagers;
use Capell\Mosaic\Actions\ReplicateContentAction;
use Capell\Mosaic\Enums\LivewireComponentsEnum;
use Capell\Mosaic\Enums\ResourceEnum;
use Capell\Mosaic\Filament\Actions\CreateContentAction;
use Capell\Mosaic\Filament\Resources\Sections\SectionResource;
use Capell\Mosaic\Filament\Resources\Sections\Widgets\SectionAlertsWidget;
use Capell\Mosaic\Models\Section;
use Filament\Actions\ActionGroup;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Howdu\FilamentRecordSwitcher\Filament\Concerns\HasRecordSwitcher;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Override;

/**
 * @property Section $record
 */
#[On('$refresh')]
class EditSection extends EditRecord
{
    use HasAncestorBreadcrumbs;
    use HasPageCacheNotification;
    use HasRecordSwitcher {
        afterSave as recordSwitcherAfterSave;
    }
    use HasTypeRelationManagers;

    /** @return class-string<SectionResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Section);
    }

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return new HtmlString(
            __(
                'capell-mosaic::heading.edit_content_record',
                ['name' => Str::limit($this->getRecordTitle(), 40)],
            ),
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        $type = $this->record->type;

        if (! $type) {
            return null;
        }

        return __('capell-mosaic::heading.content_type', [
            'type' => $type->name,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            RestoreAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            ActionGroup::make([
                CreateContentAction::make()
                    ->redirectAfterCreate(),
                ReplicateAction::make()
                    ->replicaModelAction(ReplicateContentAction::class)
                    ->hidden($this->record->trashed()),
            ]),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            SectionAlertsWidget::class,
        ];
    }

    protected function afterSave(): void
    {
        $this->notifyPageCached($this->record);

        $this->dispatch('refresh-alerts')->to(LivewireComponentsEnum::ContentAssetsTable->value);

        $this->recordSwitcherAfterSave();
    }
}
