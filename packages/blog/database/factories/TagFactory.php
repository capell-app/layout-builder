<?php

declare(strict_types=1);

namespace Capell\Blog\Database\Factories;

use Capell\Blog\Enums\TagTypeEnum;
use Capell\Blog\Models\Tag;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => ['en' => $name],
            'slug' => ['en' => Str::slug($name)],
            'type' => fake()->randomElement(['content', 'page']),
            'status' => true,
            'site_id' => null,
            'created_at' => fake()->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => fake()->dateTimeBetween('-5 month'),
        ];
    }

    public function translate(Language $language): self
    {
        return $this->state(function (array $attributes) use ($language): array {
            $name = fake()->words(2, true);

            $nameTranslations = $attributes['name'] ?? [];
            $slugTranslations = $attributes['slug'] ?? [];

            $nameTranslations[$language->code] = $name;
            $slugTranslations[$language->code] = Str::slug($name);

            return [
                'name' => $nameTranslations,
                'slug' => $slugTranslations,
            ];
        });
    }

    public function type(TagTypeEnum $type): self
    {
        return $this->set('type', $type->value);
    }

    public function site(?Site $site): self
    {
        return $this->set('site_id', $site?->id);
    }
}
