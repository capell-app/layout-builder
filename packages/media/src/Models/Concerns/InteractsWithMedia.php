<?php

declare(strict_types=1);

namespace Capell\Media\Models\Concerns;

use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\MediaConversionEnum;
use Capell\Media\Support\MediaLibrary\CustomPathGenerator;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait InteractsWithMedia
{
    use \Spatie\MediaLibrary\InteractsWithMedia;

    public static function bootInteractsWithMedia(): void
    {
        $morphClass = resolve(static::class)->getMorphClass();
        $customPathGenerators = config()->get('media-library.custom_path_generators', []);

        if (isset($customPathGenerators[$morphClass])) {
            return;
        }

        $customPathGenerators[$morphClass] = CustomPathGenerator::class;
        config()->set('media-library.custom_path_generators', $customPathGenerators);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if (! $media instanceof Media || ! str_starts_with($media->mime_type, 'image/')) {
            return;
        }

        foreach (MediaConversionEnum::cases() as $conversion) {
            $dimensions = $conversion->defaultDimensions();

            $this->addMediaConversion($conversion->value)
                ->fit($conversion->fit(), $dimensions['width'], $dimensions['height'])
                ->format($conversion->format());
        }
    }

    // NOTE If order_column is null, then MAX("media"."order_column") is also null, and the join will not match any rows, so the relation returns null.
    public function morphOneMedia(string $name = MediaCollectionEnum::Image->value): MorphOne
    {
        $model = config('media-library.media_model', Media::class);

        return $this->morphOne($model, 'model')
            ->where('collection_name', $name)
            ->latestOfMany('order_column');
    }
}
