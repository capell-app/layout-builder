<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets\Pages;

use Capell\Admin\Filament\Actions\DeleteAction;
use Capell\Admin\Filament\Actions\ReplicateAction;
use Capell\Admin\Filament\Concerns\HasBlueprintRelationManagers;
use Capell\Admin\Filament\Concerns\HasConfigurableFormActionPosition;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Enums\ResourceEnum;
use Capell\LayoutBuilder\Filament\Actions\CreateWidgetAction;
use Capell\LayoutBuilder\Filament\Resources\Widgets\RelationManagers\LayoutsRelationManager;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Howdu\FilamentRecordSwitcher\Filament\Concerns\HasRecordSwitcher;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Override;

/**
 * @property Widget $record
 */
class EditWidget extends EditRecord
{
    use HasBlueprintRelationManagers;
    use HasConfigurableFormActionPosition;
    use HasRecordSwitcher{
        afterSave as recordSwitcherAfterSave;
    }

    /** @return class-string<resource> */
    #[Override]
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Widget);
    }

    #[Override]
    public function getRelationManagers(): array
    {
        $relationManagers = $this->getTypeRelationManagers();

        if (! in_array(LayoutsRelationManager::class, $relationManagers, true)) {
            $relationManagers[] = LayoutsRelationManager::class;
        }

        return $relationManagers;
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return new HtmlString(
            __('capell-layout-builder::heading.edit_block_record', [
                'name' => Str::limit($this->getRecordTitle(), 40),
            ]),
        );
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        $subheading = '';

        $type = $this->record->type;

        if ($type !== null) {
            $subheading .= __('capell-layout-builder::heading.block_type', [
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

    /**
     * @return array<array-key, mixed>
     */
    protected static function getRecordSwitcherSearchColumns(): array
    {
        return ['name', '`key`', 'admin->notes'];
    }

    protected function afterSave(): void
    {
        if ($this->record->isDirty('updated_at')) {
            $this->dispatch(
                'model-updated',
                date: $this->record->updated_at->toDateTimeString(),
                diffSeconds: now()->diffInSeconds($this->record->updated_at),
            );
        }

        $this->recordSwitcherAfterSave();
    }

    #[Override]
    protected function getActions(): array
    {
        return $this->getBaseHeaderActions();
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getBaseHeaderActions(): array
    {
        return [
            RestoreAction::make('restore'),
            DeleteAction::make('delete'),
            ForceDeleteAction::make('forceDelete'),
            CreateWidgetAction::make('create')
                ->redirectAfterCreate(),
            ReplicateAction::make('replicate')
                ->hidden($this->record->trashed()),
        ];
    }

    protected function getPositionedFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getPositionedHeaderFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->submit(null)
                ->action(function (): void {
                    $this->save();
                }),
            $this->getCancelFormAction(),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
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
