<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
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
            'name' => fake()->sentence,
            'parent_id' => null,
            'parent_uuid' => null,
            'type_id' => (new ContentTypeFactory()),
            'site_id' => null,
            'uuid' => fake()->uuid,
            'meta' => fn (array $attributes): array => [
                'label' => fake()->optional()->sentence,
                'image_id' => fake()->optional() ? Media::inRandomOrder()->first()?->getKey() : null,
                'page_id' => fake()->optional() && $attributes['site_id'] ? Page::where('site_id', $attributes['site_id'])->inRandomOrder()->first()?->getKey() : null,
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
        return $this->set('parent_uuid', $parent->getUuid());
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

    public function withTranslations(?Collection $languages = null, array $data = []): self
    {
        return $this->afterCreating(function (Content $content) use ($languages, $data): void {
            $languages ??= $content->site?->languages ?? Language::all();

            $languages->each(function (Language $language) use ($content, $data): void {
                $title = $content->name.' '.$language->locale;

                $translation = Translation::factory()
                    ->make([
                        'language_id' => $language->id,
                        'translatable_type' => app(Content::class)->getMorphClass(),
                        'translatable_id' => $content->id,
                        'title' => $title,
                        ...$data,
                    ]);

                $content->translations()->create(
                    $translation->only($translation->getFillable())
                );
            });
        });
    }
}
