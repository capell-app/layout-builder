<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @method static Widget run(Widget $source, Collection<int, WidgetAsset> $assets) */
final class MakeIndependentWidgetCopyAction
{
    use AsFake;
    use AsObject;

    /** @param Collection<int, WidgetAsset> $assets */
    public function handle(Widget $source, Collection $assets): Widget
    {
        return DB::transaction(function () use ($source, $assets): Widget {
            $copy = $source->replicate()->unsetRelations();
            $copy->key = (string) Str::uuid();
            $copy->save();

            $mediaMap = $this->copyMedia($source, $copy);
            $this->remapMedia($copy, $mediaMap);

            foreach ($source->translations()->get() as $translation) {
                $translationCopy = $translation->replicate()->unsetRelations();
                $copy->translations()->save($translationCopy);
                $this->remapMedia($translationCopy, $mediaMap + $this->copyMedia($translation, $translationCopy));
            }

            foreach ($assets as $asset) {
                $assetCopy = $asset->replicate()->unsetRelations();
                $assetCopy->occurrence = 1;
                $copy->assets()->save($assetCopy);

                $this->remapMedia($assetCopy, $mediaMap + $this->copyMedia($asset, $assetCopy));
            }

            return $copy->load('assets');
        });
    }

    /** @return array<string, string> */
    private function copyMedia(Model $source, Model $copy): array
    {
        if (! $source instanceof HasMedia || ! $copy instanceof HasMedia) {
            return [];
        }

        $map = [];

        /** @var \Illuminate\Support\Collection<int, Media> $mediaItems */
        $mediaItems = $source->getMedia('*');

        foreach ($mediaItems as $media) {
            $mediaCopy = $media->copy($copy, $media->collection_name, $media->disk);
            $map[$media->uuid] = $mediaCopy->uuid;

            foreach (Translation::query()
                ->where('translatable_type', $media->getMorphClass())
                ->where('translatable_id', $media->getKey())
                ->get() as $translation) {
                $translationCopy = $translation->replicate()->unsetRelations();
                $translationCopy->translatable_type = $mediaCopy->getMorphClass();
                $translationCopy->translatable_id = $mediaCopy->id;
                $translationCopy->save();
            }
        }

        return $map;
    }

    /** @param array<string, string> $map */
    private function remapMedia(Model $copy, array $map): void
    {
        $attributes = $copy->getAttributes();

        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $attributes[$key] = strtr($value, $map);
            }
        }

        $copy->setRawAttributes($attributes);
        $copy->save();
    }
}
