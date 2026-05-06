<?php

declare(strict_types=1);

namespace Capell\ContentSections\Database\Factories;

use Capell\ContentSections\Models\Section;
use Capell\Core\Database\Factories\Concerns\HasFactoryPublishDates;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    use HasFactoryPublishDates;

    protected $model = Section::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Section ' . Str::uuid()->toString(),
            'parent_id' => null,
            'type_id' => (new ContentTypeFactory),
            'site_id' => null,
            'meta' => [
                'label' => null,
            ],
            'order' => 0,
            'visible_from' => now()->subYear(),
            'visible_until' => now()->subMonths(5),
            'created_at' => now()->subYear(),
            'updated_at' => now()->subMonths(5),
        ];
    }

    public function parent(Section $parent): self
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
        return $this->afterCreating(function (Section $section) use ($languages, $data): void {
            if ($languages instanceof Language) {
                $languages = collect([$languages]);
            } elseif (is_array($languages)) {
                $languages = collect($languages);
            } elseif ($section->site) {
                $languages = $section->site->languages;
            } else {
                $languages = Language::all();
            }

            if ($section->site && $languages->doesntContain('id', $section->site->language->id)) {
                $languages = $languages->prepend($section->site->language);
            }

            $languages->each(function (Language $language) use ($section, $data): void {
                if ($section->translations()->where('language_id', $language->id)->exists()) {
                    return;
                }

                $title = $section->name . ' ' . $language->locale;

                $translation = Translation::factory()
                    ->make([
                        'language_id' => $language->id,
                        'translatable_type' => resolve(Section::class)->getMorphClass(),
                        'translatable_id' => $section->id,
                        'title' => $title,
                        ...$data,
                    ]);

                $section->translations()->create(
                    $translation->only($translation->getFillable()),
                );
            });
        });
    }
}
