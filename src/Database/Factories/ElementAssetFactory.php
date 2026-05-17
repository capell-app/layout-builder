<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Database\Factories;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ElementAsset>
 */
class ElementAssetFactory extends Factory
{
    protected $model = ElementAsset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'layout_element_id' => Element::factory(),
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

    public function element(Element $element): self
    {
        return $this->state(fn (array $attributes): array => [
            'layout_element_id' => $element->id,
        ]);
    }

    public function widget(Element $element): self
    {
        return $this->element($element);
    }

    public function assetHavingMedia(int $mediaCount = 1, MediaCollectionEnum $collection = MediaCollectionEnum::Image): self
    {
        return $this->afterCreating(function (ElementAsset $elementAsset) use ($mediaCount, $collection): void {
            Media::factory()
                ->count($mediaCount)
                ->state(fn (array $attributes): array => [
                    'model_type' => $elementAsset->asset_type,
                    'model_id' => $elementAsset->asset_id,
                ])
                ->collection($collection)
                ->create();
        });
    }

    private function resolveAssetType(AssetEnum|string $asset): string
    {
        if ($asset instanceof AssetEnum) {
            return $asset->value;
        }

        return $asset;
    }

    private function createAssetRecord(AssetEnum|string $asset): Model
    {
        $assetType = $this->resolveAssetType($asset);
        $modelClass = CapellCore::getAsset($assetType)->model;

        return $modelClass::factory()->create();
    }
}
