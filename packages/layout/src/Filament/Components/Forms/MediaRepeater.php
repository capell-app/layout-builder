<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Capell\Admin\Filament\Components\Forms\Media\ImageMediaPicker;
use Capell\Core\Models\Media;
use Exception;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MediaRepeater
{
    public static function make(bool $prependImage = false): Repeater
    {
        return Repeater::make('media')
            ->statePath('media')
            ->label(__('capell-admin::form.images'))
            ->hiddenLabel()
            ->addActionLabel(__('capell-admin::button.add_media'))
            ->columnSpanFull()
            ->collapsible()
            ->grid()
            ->itemLabel(static::getItemLabel(...))
            ->schema([
                ImageMediaPicker::make('image_id')
                    ->required()
                    ->hiddenLabel(),
            ])
            ->when(
                $prependImage,
                fn (Repeater $repeater): Repeater => $repeater->afterStateHydrated(
                    function (Repeater $component, ?Model $record): void {
                        if (! $record instanceof Model) {
                            return;
                        }

                        $items = [];

                        if ($record->image) {
                            $imageData = ['image_id' => [(string) Str::uuid() => $record->image->toArray()]];

                            $uuid = $component->generateUuid();

                            if ($uuid) {
                                $items[$uuid] = $imageData;
                            } else {
                                $items[] = $imageData;
                            }
                        }

                        foreach ($record->meta['media'] ?? [] as $imageId) {
                            $media = Media::find($imageId);

                            if (! $media) {
                                throw new Exception('Media not found');
                            }

                            $uuid = $component->generateUuid();

                            if ($uuid) {
                                $items[$uuid] = ['image_id' => [(string) Str::uuid() => $media->toArray()]];
                            } else {
                                $items[] = ['image_id' => [(string) Str::uuid() => $media->toArray()]];
                            }
                        }

                        $component->state($items);
                    }
                )
                    ->afterStateUpdated(function (?array $state, Set $set): void {
                        if ($state !== null && $state !== []) {
                            $firstItem = array_shift($state);

                            $imageId = Arr::first($firstItem['image_id'])['id'] ?? null;
                        } else {
                            $imageId = null;
                        }

                        $set('image_id', $imageId);
                    })
                    ->mutateDehydratedStateUsing(static function (Repeater $component, ?array $state): array {
                        if ($state !== null && $state !== []) {
                            array_shift($state);
                        }

                        return collect($state ?? [])
                            ->values()
                            ->pluck('image_id')
                            ->all();
                    })
            );
    }

    private static function getItemLabel(Schema $schema, array $state, string $uuid): ?string
    {
        $order = collect($schema->getParentComponent()->getState())
            ->keys()
            ->search($uuid) + 1;

        $imageId = $state['image_id'] ?? null;

        if (is_array($imageId)) {
            $imageId = Arr::first($imageId)['id'] ?? null;
        }

        return $order.'. '.once(fn (): ?string => Media::find($imageId, 'title')?->title).($order === 1 ? ' ('.__('capell-admin::generic.primary').')' : '');
    }
}
