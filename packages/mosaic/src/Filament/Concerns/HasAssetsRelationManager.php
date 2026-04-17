<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Concerns;

use Aimeos\Nestedset\NestedSet;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Data\AssetData;
use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Facades\CapellCore;
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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @mixin RelationManager
 */
trait HasAssetsRelationManager
{
    protected static function createResourcesAction(): Action
    {
        return CreateAction::make()
            ->label(__('capell-mosaic::button.add_asset'))
            ->color('primary')
            ->successNotificationTitle(__('capell-mosaic::message.asset_added'))
            ->using(function (array $data, self $livewire): Model {
                throw_if(! isset($data['asset_id']), RuntimeException::class, 'No asset selected');

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
                        $asset->model === Page::class,
                        fn (Builder $query) => $query->with([
                            'ancestors',
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

                    $adminAsset = CapellAdmin::getAsset($asset->name);

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
                                ->title(__('capell-mosaic::message.asset_created_successfully', ['name' => $asset->name]))
                                ->body($page->name)
                                ->send();

                            return $page->getKey();
                        })
                        ->preload()
                        ->searchable();
                },
            );
    }

    protected static function getPageOptionLabel(Pageable $page): HtmlString
    {
        $label = $page->site->name . ' &raquo; ';

        if ($page instanceof Page) {
            $ancestors = $page->ancestors()->get();

            if ($ancestors->isNotEmpty()) {
                $label .= $ancestors->pluck('name')
                    ->map(fn (string $name): string => Str::limit($name, 30))
                    ->implode(' &raquo; ')
                    . ' &raquo; ';
            }
        }

        return new HtmlString($label . Str::limit($page->name, 40));
    }
}
