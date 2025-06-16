<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\ContentResource\Pages;

use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\DeleteAction;
use Capell\Admin\Filament\Actions\Page\ChangeTypeAction;
use Capell\Admin\Filament\Actions\ReplicateAction;
use Capell\Admin\Filament\Concerns\HasAncestorBreadcrumbs;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Admin\Filament\Concerns\HasTypeRelationManagers;
use Capell\Layout\Actions\ReplicateContentAction;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Filament\Actions\Page\CreateContentAction;
use Capell\Layout\Filament\Components\Forms\Content\ContentTypeSelect;
use Capell\Layout\Filament\Resources\ContentResource;
use Capell\Layout\Models\Content;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Howdu\FilamentRecordSwitcher\Filament\Concerns\HasRecordSwitcher;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * @property Content $record
 */
class EditContent extends EditRecord
{
    use HasAncestorBreadcrumbs;
    use HasPageCacheNotification;
    use HasRecordSwitcher {
        afterSave as recordSwitcherAfterSave;
    }
    use HasTypeRelationManagers;

    /** @return class-string<ContentResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(LayoutResourceEnum::Content->value);
    }

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return new HtmlString(
            __(
                'capell-admin::heading.edit_content_record',
                ['name' => Str::limit($this->getRecordTitle(), 40)]
            )
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        $type = $this->record->type;

        if (! $type) {
            return null;
        }

        return __('capell-admin::heading.content_type', [
            'type' => $type->name,
        ]);
    }

    protected function getActions(): array
    {
        return [
            Actions\RestoreAction::make(),
            DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\ActionGroup::make([
                CreateContentAction::make(),
                ReplicateAction::make()
                    ->replicaModelAction(ReplicateContentAction::class)
                    ->hidden($this->record->trashed()),
                ChangeTypeAction::make('editType')
                    ->typeComponent(ContentTypeSelect::class),
            ]),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = parent::mutateFormDataBeforeSave($data);

        if (isset($data['meta']['image_id'])) {
            $data['meta']['image_id'] = FixCuratorMetaDataAction::run($data['meta']['image_id']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->notifyPageCached($this->record);

        $this->recordSwitcherAfterSave();
    }
}
