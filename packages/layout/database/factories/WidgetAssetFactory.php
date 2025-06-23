<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Layout\Enums\AssetEnum as LayoutAssetEnum;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WidgetAsset>
 */
class WidgetAssetFactory extends Factory
{
    protected $model = WidgetAsset::class;

    public function container(?string $containerKey): self
    {
        return $this->state(fn (array $attributes): array => [
            'container' => $containerKey,
        ]);
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $assetType = $this->faker->randomElement([
            AssetEnum::Page,
            AssetEnum::Media,
            LayoutAssetEnum::Content,
        ]);

        return [
            'widget_id' => Widget::factory(),
            'page_id' => null,
            'asset_type' => $assetType->value,
            'asset_id' => fn (): string => match ($assetType) {
                LayoutAssetEnum::Content => (string) Content::factory()->create()->uuid,
                AssetEnum::Media => (string) Media::factory()->create()->uuid,
                AssetEnum::Page => (string) Page::factory()->create()->uuid,
            },
            'occurrence' => null,
            'order' => $this->faker->randomNumber(1),
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => $this->faker->dateTimeBetween('-5 month'),
        ];
    }

    public function occurrence(int $occurrence): self
    {
        return $this->state(fn (array $attributes): array => [
            'occurrence' => $occurrence,
        ]);
    }

    public function page(Page $page, string $container, int $occurrence): self
    {
        return $this->state(fn (array $attributes): array => [
            'page_id' => $page->id,
            'container' => $container,
            'occurrence' => $occurrence,
        ]);
    }

    public function asset(AssetEnum|LayoutAssetEnum $type): self
    {
        return $this->state(fn (array $attributes): array => [
            'asset_type' => $type->value,
            'asset_id' => fn (): string => match ($type) {
                LayoutAssetEnum::Content => (string) Content::factory()->withTranslations()->create()->uuid,
                AssetEnum::Media => (string) Media::factory()->create()->uuid,
                AssetEnum::Page => (string) Page::factory()->withTranslations()->create()->uuid,
            },
        ]);
    }

    public function widget(Widget $widget): self
    {
        return $this->state(fn (array $attributes): array => [
            'widget_id' => $widget->id,
        ]);
    }
}
