<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Resources\ContentBlocks\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\DeleteAction;
use Capell\Admin\Filament\Actions\ReplicateAction;
use Capell\Admin\Filament\Concerns\HasAncestorBreadcrumbs;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Admin\Filament\Concerns\HasTypeRelationManagers;
use Capell\ContentBlocks\Actions\ReplicateContentAction;
use Capell\ContentBlocks\Enums\LivewireComponentsEnum;
use Capell\ContentBlocks\Enums\ResourceEnum;
use Capell\ContentBlocks\Filament\Actions\CreateContentAction;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\ContentBlockResource;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\Widgets\ContentBlockAlertsWidget;
use Capell\ContentBlocks\Models\ContentBlock;
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
 * @property ContentBlock $record
 */
#[On('$refresh')]
class EditContentBlock extends EditRecord
{
    use HasAncestorBreadcrumbs;
    use HasPageCacheNotification;
    use HasRecordSwitcher {
        afterSave as recordSwitcherAfterSave;
    }
    use HasTypeRelationManagers;

    /** @return class-string<ContentBlockResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::ContentBlock);
    }

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return new HtmlString(
            __(
                'capell-content-blocks::heading.edit_content_record',
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

        return __('capell-content-blocks::heading.content_type', [
            'type' => $type->name,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            RestoreAction::make('restore'),
            DeleteAction::make('delete'),
            ForceDeleteAction::make('forceDelete'),
            CreateContentAction::make('create')
                ->redirectAfterCreate(),
            ReplicateAction::make('replicate')
                ->replicaModelAction(ReplicateContentAction::class)
                ->hidden($this->record->trashed()),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            ContentBlockAlertsWidget::class,
        ];
    }

    protected function afterSave(): void
    {
        $this->notifyPageCached($this->record);

        $this->dispatch('refresh-alerts')->to(LivewireComponentsEnum::ContentAssetsTable->value);

        $this->recordSwitcherAfterSave();
    }
}
