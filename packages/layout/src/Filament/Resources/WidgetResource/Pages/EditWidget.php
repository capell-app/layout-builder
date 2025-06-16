<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\WidgetResource\Pages;

use Capell\Admin\Contracts\PageCacheNotifiable;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\DeleteAction;
use Capell\Admin\Filament\Actions\Page\ChangeTypeAction;
use Capell\Admin\Filament\Actions\ReplicateAction;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Admin\Filament\Concerns\HasTypeRelationManagers;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Filament\Actions\Page\CreateWidgetAction;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTypeSelect;
use Capell\Layout\Filament\Resources\WidgetResource;
use Capell\Layout\Filament\Resources\WidgetResource\RelationManagers;
use Capell\Layout\Models\Widget;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Table;
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
    use HasTypeRelationManagers {
        getRelationManagers as getRelationManagersTrait;
    }

    /** @return class-string<WidgetResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(LayoutResourceEnum::Widget->value);
    }

    public function getRelationManagers(): array
    {
        $relationManagers = $this->getRelationManagersTrait();

        if (! in_array(RelationManagers\WidgetAssetsRelationManager::class, $relationManagers, true)) {
            $relationManagers[] = RelationManagers\WidgetAssetsRelationManager::class;
        }

        $relationManagers[] = RelationManagers\LayoutsRelationManager::class;

        return $relationManagers;
    }

    public function getTitle(): string|Htmlable
    {
        return new HtmlString(
            __('capell-admin::heading.edit_widget_record', [
                'name' => Str::limit($this->getRecordTitle(), 40),
            ])
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        $type = $this->record->type;

        if (! $type) {
            return null;
        }

        return __('capell-admin::heading.widget_type', [
            'type' => $type->name,
        ]);
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
                date: $this->record->updated_at->translatedFormat(Table::$defaultDateTimeDisplayFormat),
                diffSeconds: now()->diffInSeconds($this->record->updated_at)
            );
        }

        $this->notifyPageCached($this->record);

        $this->recordSwitcherAfterSave();
    }

    protected function getActions(): array
    {
        return [
            Actions\RestoreAction::make(),
            DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\ActionGroup::make([
                CreateWidgetAction::make(),
                ReplicateAction::make()
                    ->hidden($this->record->trashed()),
                ChangeTypeAction::make('editType')
                    ->typeComponent(WidgetTypeSelect::class),
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

        $updated_at = $model::find($this->record->id, [$attribute])->value($attribute);

        return ! $updated_at || $this->record->updated_at > $updated_at;
    }
}
