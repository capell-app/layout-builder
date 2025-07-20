<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Forms\Media\ImageMediaPicker;
use Capell\Admin\Filament\Components\Forms\Page\PageSelect;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Layout\Filament\Components\Forms\AssetTypeToggleButtons;
use Capell\Layout\Filament\Components\Forms\Content\ContentSelect;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\WidgetAsset;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WidgetAssetsRepeater
{
    public static function make(Schema $schema): Repeater
    {
        return Repeater::make('assets')
            ->label(__('capell-admin::form.assets'))
            ->relationship('widgetAssets')
            ->columnSpanFull()
            ->reorderable()
            ->collapsed()
            ->defaultItems(0)
            ->addActionLabel(__('capell-admin::button.add_asset'))
            ->itemLabel(function (Schema $schema, array $state, string $uuid): string {
                $order = collect($schema->getParentComponent()->getState())
                    ->keys()
                    ->search($uuid) + 1;

                $label = $order.'. ';

                if (empty($state['asset_id'])) {
                    return $label.__('capell-admin::generic.select_resource');
                }

                return $label.self::getAssetName($state).' ('.str($state['asset_type'])->title().')';
            })
            ->extraItemActions([
                Action::make('editRecord')
                    ->label(function (Repeater $component, array $arguments): string {
                        $itemState = $component->getRawItemState((string) $arguments['item']);

                        return __('capell-admin::button.edit_resource', ['type' => $itemState['asset_type']]);
                    })
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->tooltip(fn (Action $action): string => $action->getLabel())
                    ->hidden(function (Repeater $component, array $arguments): bool {
                        $itemState = $component->getRawItemState((string) $arguments['item']);

                        return empty($itemState['asset_id']);
                    })
                    ->url(
                        function (array $arguments, Repeater $component): ?string {
                            $itemState = $component->getRawItemState((string) $arguments['item']);

                            $assetId = $itemState['asset_id'] ?? null;

                            if (empty($itemState['asset_type']) || empty($assetId)) {
                                return null;
                            }

                            $resource = match ($itemState['asset_type']) {
                                'media' => is_array($assetId) ? reset($assetId)['id'] : Media::findByUuid($assetId),
                                'page' => Page::findByUuid($assetId),
                                'content' => Content::findByUuid($assetId),
                            };

                            if (! $resource) {
                                throw new Exception(sprintf("Resource '%s' not found for ID '%s'", $itemState['asset_type'], $assetId));
                            }

                            return CapellAdmin::getResource($itemState['asset_type'])::getUrl(
                                'edit',
                                ['record' => $resource]
                            );
                        },
                        shouldOpenInNewTab: true
                    ),
            ])
            ->schema([
                Group::make()
                    ->schema(function (?WidgetAsset $record): array {
                        if ($record instanceof WidgetAsset) {
                            return self::getEditResourceSchema($record);
                        }

                        return self::getCreateResourceSchema();
                    }),
            ]);
    }

    private static function getCreateResourceSchema(): array
    {
        return [
            AssetTypeToggleButtons::make('asset_type')
                ->required()
                ->reactive()
                ->afterStateUpdated(function (Set $set): void {
                    $set('asset_id', null);
                }),
            Group::make()
                ->visible(fn (Get $get): bool => $get('asset_type') === 'media')
                ->schema([
                    ImageMediaPicker::make('asset_id')
                        ->required(),
                ]),
            Group::make()
                ->visible(fn (Get $get): bool => $get('asset_type') === 'page')
                ->schema([
                    PageSelect::make('asset_id')
                        ->required()
                        ->withCreateForm()
                        ->withEditForm(),
                ]),
            Group::make()
                ->visible(fn (Get $get): bool => $get('asset_type') === 'content')
                ->schema([
                    ContentSelect::make('asset_id')
                        ->required()
                        ->withCreateForm()
                        ->withEditForm(),
                ]),
        ];
    }

    private static function getEditResourceSchema(?WidgetAsset $record): array
    {
        return [
            Group::make()
                ->schema([
                    match ($record->asset_type) {
                        'media' => ImageMediaPicker::make('asset_id')
                            ->required()
                            ->afterStateHydrated(static function (ImageMediaPicker $component, array|int|string|null $state): void {
                                if (blank($state)) {
                                    $component->state([]);

                                    return;
                                }

                                $items = [];

                                $state = is_array($state) ? array_values($state) : $state;

                                if (is_array($state) && isset($state[0]['id'])) {
                                    $media = $state;
                                } elseif (isset($state['id'])) {
                                    $media = [$state];
                                } else {
                                    $state = Arr::wrap($state);

                                    // @custom
                                    $media = Media::query()->where('id', $state)->get()->toArray();
                                }

                                foreach ($media as $itemData) {
                                    $items[(string) Str::uuid()] = $itemData;
                                }

                                $component->state($items);
                            }),
                        'page' => PageSelect::make('asset_id')
                            ->required()
                            ->withCreateForm()
                            ->withEditForm(),
                        'content' => ContentSelect::make('asset_id')
                            ->required()
                            ->withCreateForm()
                            ->withEditForm(),
                    },
                ]),
        ];
    }

    private static function getAssetName(array $itemState): ?string
    {
        return match ($itemState['asset_type']) {
            'media' => is_array($itemState['asset_id']) ? reset($itemState['asset_id'])['title'] : Media::findByUuid($itemState['asset_id']),
            'page' => Page::findByUuid($itemState['asset_id'])?->name,
            'content' => Content::findByUuid($itemState['asset_id'])?->name
        };
    }
}
