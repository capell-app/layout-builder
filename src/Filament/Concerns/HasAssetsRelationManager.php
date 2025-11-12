<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Concerns;

use Capell\Core\Data\AssetData;
use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Contracts\Draftable;
use Capell\Core\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NestedSet;
use RuntimeException;

/**
 * @mixin RelationManager
 */
trait HasAssetsRelationManager
{
    protected static function createResourcesAction(): Action
    {
        return CreateAction::make()
            ->label(__('capell-admin::button.add_asset'))
            ->color('primary')
            ->successNotificationTitle(__('capell-admin::message.asset_added'))
            ->using(function (array $data, self $livewire): Model {
                throw_if(empty($data['asset_id']), RuntimeException::class, 'No asset selected');

                $asset = null;

                foreach ($data['asset_id'] as $uuid) {
                    $asset = $livewire->ownerRecord->assets()->create([
                        'asset_id' => $uuid,
                        'asset_type' => $data['asset_type'],
                        'related_type' => $livewire->ownerRecord->getMorphClass(),
                        'related_id' => $livewire->ownerRecord->getKey(),
                    ]);
                }

                return $asset;
            });
    }

    protected static function getAssetForm(): array
    {
        return [
            MorphToSelect::make('asset')
                ->types(
                    fn (self $livewire) => CapellCore::getAssets()
                        ->map(fn (AssetData $asset): Type => self::getMorphToSelectType($asset, $livewire->ownerRecord))
                        ->toArray(),
                )
                ->modifyKeySelectUsing(fn (Select $select): Select => $select->multiple()),
        ];
    }

    protected static function getMorphToSelectType(AssetData $asset, Model $record): Type
    {
        return Type::make($asset->model)
            ->titleAttribute($asset->getTitleKey())
            ->modifyOptionsQueryUsing(
                fn (Builder $query) => $query->when(
                    $record instanceof $asset->model,
                    fn (Builder $query) => $query->whereKeyNot($record->id),
                )
                    ->whereDoesntHave(
                        'assetRelations',
                        fn (Builder $relationship) => $relationship->where(
                            'related_type',
                            $record->getMorphClass(),
                        )
                            ->where('related_id', $record->getKey()),
                    )
                    ->when(
                        in_array(Draftable::class, class_implements($asset->model), true),
                        fn (Builder $query) => $query->withDrafts(),
                    )
                    ->when(
                        $asset->model === Page::class,
                        fn (Builder $query) => $query->with([
                            'ancestors' => fn (Relation $query) => $query->withDrafts(),
                            'site',
                        ])
                            ->whereHas(
                                'type',
                                fn (Builder $query) => $query->where(
                                    fn (Builder $query) => $query->where(
                                        'group',
                                        '!=',
                                        TypeGroupEnum::System->value,
                                    )
                                        ->orWhereNull('group'),
                                ),
                            )
                            ->orderBy('site_id'),
                    )
                    ->when(
                        in_array(NestedSet::class, class_uses_recursive($asset->model), true),
                        fn (Builder $query) => $query->defaultOrder(),
                    ),
            )
            ->getOptionLabelFromRecordUsing(
                fn (Model $record): string|HtmlString => match ($record::class) {
                    Page::class => self::getPageOptionLabel($record),
                    default => $record->getAttributeValue($asset->getTitleKey()),
                },
            )
            ->modifyKeySelectUsing(
                function (Select $select) use ($asset): Select {
                    $createOptionUsing = $select->getCreateOptionUsing();

                    $adminAsset = CapellCore::getAsset($asset->name);

                    return $select->createOptionForm(
                        fn (Schema $schema): Schema => $adminAsset->formClass::configure(
                            $schema->operation('createOption')->model($asset->model),
                        ),
                    )
                        ->createOptionUsing(function (Select $component, array $data) use ($asset, $adminAsset, $createOptionUsing): int|string {
                            $page = $adminAsset->createAction
                                ? $adminAsset->createAction::run($data)
                                : $component->evaluate($createOptionUsing);

                            Notification::make()
                                ->title(__('capell-admin::message.asset_created_successfully', ['name' => $asset->name]))
                                ->body($page->name)
                                ->send();

                            return $page->getKey();
                        })
                        ->preload()
                        ->searchable();
                },
            );
    }

    protected static function getPageOptionLabel(Page $page): HtmlString
    {
        $label = $page->site->name . ' &raquo; ';

        $ancestors = $page->ancestors()->get();

        if ($ancestors->isNotEmpty()) {
            $label .= $ancestors->pluck('name')
                ->map(fn ($item) => Str::limit($item, 30))
                ->implode(' &raquo; ')
                . ' &raquo; ';
        }

        return new HtmlString($label . Str::limit($page->name, 40));
    }
}
