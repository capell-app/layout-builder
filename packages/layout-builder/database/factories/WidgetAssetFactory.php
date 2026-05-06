<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Database\Factories;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Enums\ActionLinkEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
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
        return [
            'widget_id' => Widget::factory(),
            'asset_type' => AssetEnum::Page->value,
            'asset_id' => fn (): string => (string) Page::factory()->withTranslations()->create()->getKey(),
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

    public function asset(AssetEnum|string|Model $asset): self
    {
        return $this->state(fn (array $attributes): array => [
            'asset_type' => $asset instanceof Model ? $asset->getMorphClass() : $this->resolveAssetType($asset),
            'asset_id' => fn (): mixed => $asset instanceof Model
                ? $asset->getKey()
                : $this->createAssetRecord($asset)->getKey(),
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
            $related = Page::factory()
                ->count($count)
                ->withTranslations()
                ->create();

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

    private function resolveAssetType(AssetEnum|string $asset): string
    {
        return $asset instanceof AssetEnum ? $asset->value : mb_strtolower($asset);
    }

    private function createAssetRecord(AssetEnum|string $asset): Model
    {
        $assetType = $this->resolveAssetType($asset);

        if ($assetType === AssetEnum::Page->value) {
            return Page::factory()->withTranslations()->create();
        }

        $registeredType = ucfirst($assetType);

        if (! CapellCore::hasAsset($registeredType)) {
            return Page::factory()->withTranslations()->create();
        }

        $model = CapellCore::getAsset($registeredType)->model;

        if (! method_exists($model, 'factory')) {
            return Page::factory()->withTranslations()->create();
        }

        return $model::factory()->create();
    }
}
