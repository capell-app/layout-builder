<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Layout\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    protected $model = Content::class;

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
            'type_id' => (new ContentTypeFactory),
            'site_id' => null,
            'meta' => [
                'label' => fake()->optional()->sentence(),
            ],
            'order' => fake()->numberBetween(1, 100),
            'publish_from' => fake()->dateTimeBetween('-1 year', '-6 month'),
            'publish_to' => fake()->dateTimeBetween('-5 month'),
            'created_at' => fake()->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => fake()->dateTimeBetween('-5 month'),
        ];
    }

    public function parent(Content $parent): self
    {
        return $this->set('parent_id', $parent->getKey());
    }

    public function published(): self
    {
        return $this->state(fn (array $attributes): array => [
            'publish_from' => fake()->dateTimeBetween('-1 year', '-6 month'),
            'publish_to' => fake()->dateTimeBetween('-5 month'),
        ]);
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
        return $this->state(fn (array $attributes): array => [
            'meta' => array_merge(
                $attributes['meta'] ?? [],
                [
                    'page_id' => Page::factory()->withTranslations()->create()->id,
                ],
            ),
        ]);
    }

    public function withTranslations(?Collection $languages = null, array $data = []): self
    {
        return $this->afterCreating(function (Content $content) use ($languages, $data): void {
            $languages ??= $content->site?->languages ?? Language::all();

            $languages->each(function (Language $language) use ($content, $data): void {
                if ($content->translations()->where('language_id', $language->id)->exists()) {
                    return;
                }

                $title = $content->name . ' ' . $language->locale;

                $translation = Translation::factory()
                    ->make([
                        'language_id' => $language->id,
                        'translatable_type' => resolve(Content::class)->getMorphClass(),
                        'translatable_id' => $content->id,
                        'title' => $title,
                        ...$data,
                    ]);

                $content->translations()->create(
                    $translation->only($translation->getFillable()),
                );
            });
        });
    }
}
