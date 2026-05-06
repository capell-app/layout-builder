<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Database\Factories;

use Capell\BlockLibrary\Models\ContentBlock;
use Capell\Core\Database\Factories\Concerns\HasFactoryPublishDates;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @extends Factory<ContentBlock>
 */
class ContentBlockFactory extends Factory
{
    use HasFactoryPublishDates;

    protected $model = ContentBlock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(),
            'parent_id' => null,
            'type_id' => (new ContentBlockTypeFactory),
            'site_id' => null,
            'meta' => [
                'label' => fake()->optional()->sentence(),
            ],
            'order' => fake()->numberBetween(1, 100),
            'visible_from' => fake()->dateTimeBetween('-1 year', '-6 month'),
            'visible_until' => fake()->dateTimeBetween('-5 month'),
            'created_at' => fake()->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => fake()->dateTimeBetween('-5 month'),
        ];
    }

    public function parent(ContentBlock $parent): self
    {
        return $this->set('parent_id', $parent->getKey());
    }

    public function site(Site $site): self
    {
        return $this->state(fn (array $attributes): array => [
            'site_id' => $site->id,
        ]);
    }

    public function type(Type $type): self
    {
        return $this->set('type_id', $type->getKey());
    }

    public function linkedPage(): self
    {
        return $this->state(function (array $attributes): array {
            $linkedPage = Page::factory()->withTranslations()->create();

            return [
                'meta' => array_merge(
                    $attributes['meta'] ?? [],
                    [
                        'linked_pageable_id' => $linkedPage->getKey(),
                        'linked_pageable_type' => $linkedPage->getMorphClass(),
                    ],
                ),
            ];
        });
    }

    public function withTranslations(null|array|SupportCollection|Language $languages = null, array $data = []): self
    {
        return $this->afterCreating(function (ContentBlock $contentBlock) use ($languages, $data): void {
            if ($languages instanceof Language) {
                $languages = collect([$languages]);
            } elseif (is_array($languages)) {
                $languages = collect($languages);
            } elseif ($contentBlock->site) {
                $languages = $contentBlock->site->languages;
            } else {
                $languages = Language::all();
            }

            if ($contentBlock->site && $languages->doesntContain('id', $contentBlock->site->language->id)) {
                $languages = $languages->prepend($contentBlock->site->language);
            }

            $languages->each(function (Language $language) use ($contentBlock, $data): void {
                if ($contentBlock->translations()->where('language_id', $language->id)->exists()) {
                    return;
                }

                $title = $contentBlock->name . ' ' . $language->locale;

                $translation = Translation::factory()
                    ->make([
                        'language_id' => $language->id,
                        'translatable_type' => resolve(ContentBlock::class)->getMorphClass(),
                        'translatable_id' => $contentBlock->id,
                        'title' => $title,
                        ...$data,
                    ]);

                $contentBlock->translations()->create(
                    $translation->only($translation->getFillable()),
                );
            });
        });
    }
}
