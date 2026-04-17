<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Pages;

use Capell\Admin\Contracts\PageCacheNotifiable;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\DeleteAction;
use Capell\Admin\Filament\Actions\ReplicateAction;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Admin\Filament\Concerns\HasTypeRelationManagers;
use Capell\Mosaic\Enums\ResourceEnum;
use Capell\Mosaic\Filament\Actions\CreateWidgetAction;
use Capell\Mosaic\Filament\Resources\Widgets\RelationManagers\LayoutsRelationManager;
use Capell\Mosaic\Filament\Resources\Widgets\WidgetResource;
use Capell\Mosaic\Models\Widget;
use Filament\Actions\ActionGroup;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Howdu\FilamentRecordSwitcher\Filament\Concerns\HasRecordSwitcher;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * @property Widget $record
 */
class EditWidget extends EditRecord implements PageCacheNotifiable
{
    use HasPageCacheNotification;
    use HasRecordSwitcher{
        afterSave as recordSwitcherAfterSave;
    }
    use HasTypeRelationManagers;

    /** @return class-string<WidgetResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Widget);
    }

    public function getRelationManagers(): array
    {
        $relationManagers = $this->getTypeRelationManagers();

        if (! in_array(LayoutsRelationManager::class, $relationManagers, true)) {
            $relationManagers[] = LayoutsRelationManager::class;
        }

        return $relationManagers;
    }

    public function getTitle(): string|Htmlable
    {
        return new HtmlString(
            __('capell-mosaic::heading.edit_widget_record', [
                'name' => Str::limit($this->getRecordTitle(), 40),
            ]),
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        $subheading = '';

        $type = $this->record->type;

        if ($type) {
            $subheading .= __('capell-mosaic::heading.widget_type', [
                'type' => $type->name,
            ]);
        }

        if ($this->record->isDisabled()) {
            if ($subheading !== '') {
                $subheading .= ' | ';
            }

            $subheading .= '<span class="text-red-600 dark:text-red-400 font-medium">'
                . __('capell-admin::generic.disabled') . '</span>';
        }

        return new HtmlString($subheading);
    }

    protected static function getRecordSwitcherSearchColumns(): array
    {
        return ['name', '`key`', 'admin->notes'];
    }

    protected function afterSave(): void
    {
        if ($this->record->isDirty('updated_at')) {
            $this->dispatch(
                'model-updated',
                date: $this->record->updated_at->translatedFormat($this->getTable()->getDefaultDateTimeDisplayFormat()),
                diffSeconds: now()->diffInSeconds($this->record->updated_at),
            );
        }

        $this->notifyPageCached($this->record);

        $this->recordSwitcherAfterSave();
    }

    protected function getActions(): array
    {
        return [
            RestoreAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            ActionGroup::make([
                CreateWidgetAction::make()
                    ->redirectAfterCreate(),
                ReplicateAction::make()
                    ->hidden($this->record->trashed()),
            ]),
        ];
    }

    protected function getRecordSwitcherColumns(): array
    {
        return ['name', 'admin'];
    }

    protected function selectChangerItemLabel(Widget $model): string
    {
        return $model->name;
    }

    protected function wasRecentlyChanged(string $attribute = 'updated_at'): bool
    {
        $model = $this->getModel();

        $updated_at = $model::query()->find($this->record->id, [$attribute])->value($attribute);

        return $updated_at === null || $this->record->updated_at > $updated_at;
    }
}
