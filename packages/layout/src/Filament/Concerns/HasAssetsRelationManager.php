<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Concerns;

use Capell\Admin\Actions\ModifyPageSelectCreateAction;
use Capell\Core\Data\AssetData;
use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Contracts\Draftable;
use Capell\Core\Models\Page;
use Capell\Layout\Actions\ModifyContentSelectCreateAction;
use Capell\Layout\Models\Content;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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
                if (empty($data['asset_id'])) {
                    throw new RuntimeException('No asset selected');
                }

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
                        ->toArray()
                )
                ->modifyKeySelectUsing(fn (Select $select): Select => $select->multiple()),
        ];
    }

    protected static function getMorphToSelectType(AssetData $asset, Model $record): Type
    {
        $model = $asset->getModel();

        return Type::make($model)
            ->titleAttribute($asset->getTitleKey())
            ->modifyOptionsQueryUsing(
                fn (Builder $query) => $query->when(
                    $record instanceof $model,
                    fn (Builder $query) => $query->whereKeyNot($record->id)
                )
                    ->whereDoesntHave(
                        'assetRelations',
                        fn (Builder $relationship) => $relationship->where(
                            'related_type',
                            $record->getMorphClass(),
                        )
                            ->where('related_id', $record->getKey())
                    )
                    ->when(
                        in_array(Draftable::class, class_implements($model), true),
                        fn (Builder $query) => $query->withDrafts()
                    )
                    ->when(
                        $model === Page::class,
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
                                        TypeGroupEnum::System->value
                                    )
                                        ->orWhereNull('group')
                                )
                            )
                            ->orderBy('site_id')
                    )
                    ->when(
                        in_array(NestedSet::class, class_uses_recursive($model), true),
                        fn (Builder $query) => $query->defaultOrder()
                    )
            )
            ->getOptionLabelFromRecordUsing(
                fn (Model $record): string => match ($record::class) {
                    Page::class => self::getPageOptionLabel($record),
                    default => $record->getAttributeValue($asset->getTitleKey()),
                },
            )
            ->modifyKeySelectUsing(
                // TODO make this configurable per asset type
                fn (Select $select): Select => (match ($asset->getModel()) {
                    Content::class => ModifyContentSelectCreateAction::run($select),
                    Page::class => ModifyPageSelectCreateAction::run($select),
                })
                    ->preload()
                    ->searchable()
            );
    }

    protected static function getPageOptionLabel(Page $page): string
    {
        $label = $page->site->name . ' » ';

        $ancestors = $page->ancestors()->get();

        if ($ancestors->isNotEmpty()) {
            $label .= $ancestors->pluck('name')
                ->map(fn ($item) => Str::limit($item, 30))
                ->implode(' » ')
                . ' » ';
        }

        return $label . Str::limit($page->name, 40);
    }
}
