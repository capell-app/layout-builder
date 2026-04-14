<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Layout\Enums\ActionLinkEnum;
use Capell\Layout\Enums\AssetEnum as LayoutAssetEnum;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<WidgetAsset>
 */
class WidgetAssetFactory extends Factory
{
    protected $model = WidgetAsset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $assetType = fake()->randomElement([
            AssetEnum::Page,
            LayoutAssetEnum::Content,
        ]);

        return [
            'widget_id' => Widget::factory(),
            'asset_type' => $assetType->value,
            'asset_id' => fn (): string => match ($assetType) {
                LayoutAssetEnum::Content => (string) Content::factory()->withTranslations()->linkedPage()->create()->id,
                AssetEnum::Page => (string) Page::factory()->withTranslations()->create()->id,
            },
            'pageable_id' => null,
            'pageable_type' => null,
            'occurrence' => 1,
            'order' => fake()->randomNumber(1),
            'created_at' => fake()->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => fake()->dateTimeBetween('-5 month'),
        ];
    }

    public function container(?string $containerKey): self
    {
        return $this->state(fn (array $attributes): array => [
            'container' => $containerKey,
        ]);
    }

    public function occurrence(int $occurrence): self
    {
        return $this->state(fn (array $attributes): array => [
            'occurrence' => $occurrence,
        ]);
    }

    public function page(Pageable $page, ?string $container = null, ?int $occurrence = null): self
    {
        return $this->state(fn (array $attributes): array => [
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'container' => $container ?? $this->faker->slug,
            'occurrence' => $occurrence ?? $this->faker->numberBetween(1, 10),
        ]);
    }

    public function asset(AssetEnum|LayoutAssetEnum|Model $asset): self
    {
        return $this->state(fn (array $attributes): array => [
            'asset_type' => $asset instanceof Model ? $asset->getMorphClass() : $asset->value,
            'asset_id' => fn (): mixed => $asset instanceof Model
                ? $asset->getKey()
                : match ($asset) {
                    LayoutAssetEnum::Content => (string) Content::factory()->withTranslations()->linkedPage()->create()->getKey(),
                    AssetEnum::Page => (string) Page::factory()->withTranslations()->create()->getKey(),
                },
        ]);
    }

    public function widget(Widget $widget): self
    {
        return $this->state(fn (array $attributes): array => [
            'widget_id' => $widget->id,
        ]);
    }

    public function assetHavingMedia(int $mediaCount = 1, MediaCollectionEnum $collection = MediaCollectionEnum::Image): self
    {
        return $this->afterCreating(function (WidgetAsset $widgetAsset) use ($mediaCount, $collection): void {
            Media::factory()
                ->count($mediaCount)
                ->state(fn (array $attributes): array => [
                    'model_type' => $widgetAsset->asset_type,
                    'model_id' => $widgetAsset->asset_id,
                ])
                ->collection($collection)
                ->create();
        });
    }

    public function assetHavingRelated(int $count = 1): self
    {
        return $this->afterCreating(function (WidgetAsset $widgetAsset) use ($count): void {
            $related = match ($widgetAsset->asset_type) {
                LayoutAssetEnum::Content->value => Content::factory()
                    ->count($count)
                    ->withTranslations()
                    ->linkedPage()
                    ->create(),
                AssetEnum::Page->value => Page::factory()
                    ->count($count)
                    ->withTranslations()
                    ->create(),
            };

            $meta = $widgetAsset->asset->meta;
            $meta['related'] = collect($meta['related'] ?? [])
                ->merge($related->pluck('id'))
                ->unique()
                ->values()
                ->all();
            $widgetAsset->asset->meta = $meta;
            $widgetAsset->asset->save();
        });
    }

    public function assetHavingActions(int $count): self
    {
        return $this->afterCreating(function (WidgetAsset $widgetAsset) use ($count): void {
            $actions = [];
            for ($i = 0; $i < $count; $i++) {
                $actions[] = [
                    'type' => ActionLinkEnum::Link->value,
                    'label' => fake()->sentence(2),
                    'url' => fake()->url(),
                ];
            }

            $meta = $widgetAsset->asset->meta;
            $meta['actions'] = collect($meta['actions'] ?? [])
                ->merge($actions)
                ->all();
            $widgetAsset->asset->meta = $meta;
            $widgetAsset->asset->save();
        });
    }
}
