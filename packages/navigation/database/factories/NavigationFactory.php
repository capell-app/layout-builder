<?php

declare(strict_types=1);

namespace Capell\Navigation\Database\Factories;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Database\Factories\Concerns\HasMeta;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * @extends Factory<Navigation>
 */
class NavigationFactory extends Factory
{
    use HasMeta;

    protected $model = Navigation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'key' => $this->faker->slug(),
            'items' => [],
            'type_id' => Type::factory()->navigation(),
            'language_id' => $this->faker->boolean() ? Language::factory() : null,
            'site_id' => fn (array $attributes): SiteFactory => Site::factory()
                ->language($attributes['language_id'] ?? null)
                ->withTranslations(),
        ];
    }

    public function site(Site $site): static
    {
        return $this->set('site_id', $site->getKey());
    }

    public function language(Language $language): static
    {
        return $this->set('language_id', $language->getKey());
    }

    public function items(array|Collection $items): self
    {
        return $this->set('items', is_array($items) ? $items : $this->mapPagesToItems($items));
    }

    public function defaultItems(int $count = 5): self
    {
        return $this->items([
            ...$this->mapPagesToItems(Page::factory()->count($count > 1 ? $count - 1 : 1)->create()),
            ...(
                $count > 1 ?
                [
                    [
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => 'https://example.com'],
                    ],
                ]
                : []
            ),
        ]);
    }

    private function mapPagesToItems(Collection $pages): array
    {
        return $pages->map(fn (Pageable $page): array => [
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_id' => $page->getKey(),
                'pageable_type' => $page->getMorphClass(),
                ...($this->faker->boolean() && $page->translation !== null ? ['label' => $page->translation->label] : []),
            ],
            'children' => $this->mapPagesToItems($page->children),
        ])
            ->all();
    }
}
