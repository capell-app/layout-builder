<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Layout\Enums\AssetEnum as LayoutAssetEnum;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\ContentAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentAsset>
 */
class ContentAssetFactory extends Factory
{
    protected $model = ContentAsset::class;

    public function definition(): array
    {
        $type = fake()->randomElement([
            AssetEnum::Page,
            AssetEnum::Media,
            LayoutAssetEnum::Content,
        ]);

        return [
            'content_id' => Content::factory(),
            'order' => fake()->numberBetween(1, 10),
            'asset_type' => $type->value,
            'asset_id' => fn ($state): string => (match ($type) {
                LayoutAssetEnum::Content => (string) Content::factory()->create()->id,
                AssetEnum::Media => (string) Media::factory()->create()->id,
                AssetEnum::Page => (string) Page::factory()->create()->id,
            }),
        ];
    }

    public function media(array $state = []): static
    {
        return $this->state([
            'asset_type' => 'media',
            'asset_id' => Media::factory($state)->create()->id,
        ]);
    }

    public function page(array $state = []): static
    {
        return $this->state([
            'asset_type' => 'page',
            'asset_id' => Page::factory($state)->create()->id,
        ]);
    }

    public function content(array $state = []): static
    {
        return $this->state([
            'asset_type' => 'content',
            'asset_id' => Content::factory($state)->create()->id,
        ]);
    }
}
