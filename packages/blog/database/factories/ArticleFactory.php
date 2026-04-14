<?php

declare(strict_types=1);

namespace Capell\Blog\Database\Factories;

use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Database\Factories\Concerns\HasAdmin;
use Capell\Core\Database\Factories\Concerns\HasFactoryPublishDates;
use Capell\Core\Database\Factories\Concerns\HasMeta;
use Capell\Core\Database\Factories\Concerns\HasTranslations;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    use HasAdmin;
    use HasFactoryPublishDates;
    use HasMeta;
    use HasTranslations;

    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'name' => fn () => $this->faker->realTextBetween(2, 60),
            'layout_id' => fn (): int => resolve(BlogCreator::class)->createArticleLayout()->id,
            'type_id' => fn (): int => resolve(BlogCreator::class)->createArticlePageType()->id,
            'site_id' => Site::factory()->withTranslations(),
            'created_at' => fn () => $this->faker->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => fn (array $attributes) => $this->faker->dateTimeBetween($attributes['created_at']),
        ];
    }

    public function layout(Layout $layout): static
    {
        return $this->set('layout_id', $layout->id);
    }

    public function site(int|Site $site): static
    {
        return $this->set('site_id', $site instanceof Site ? $site->id : $site);
    }

    public function type(Type $type): static
    {
        return $this->set('type_id', $type->id);
    }

    public function publisher(Model $user): self
    {
        return $this->state(fn (): array => [
            'publisher_type' => $user->getMorphClass(),
            'publisher_id' => $user->getKey(),
        ]);
    }

    public function withTags(): self
    {
        return $this->afterCreating(function (Article $article): void {
            if (Tag::query()->count() < 10) {
                Tag::factory()->count(3)->create();
            }

            $tags = Tag::query()->inRandomOrder()->limit(fake()->numberBetween(1, 3))->get();

            $article->tags()->attach($tags);
        });
    }

    public function hasDrafts(int $count = 1): static
    {
        return $this->afterCreating(function (Article $article) use ($count): void {
            $currentDraft = $count > 1 ? $this->faker->numberBetween(1, $count) : 1;

            Article::factory()
                ->count($count)
                ->site($article->site)
                ->unpublished()
                ->withTranslations($article->languages)
                ->state([
                    'uuid' => $article->uuid,
                    'published_at' => null,
                    'is_published' => false,
                ])
                ->sequence(fn (Sequence $sequence): array => [
                    'is_current' => $currentDraft === ($sequence->index + 1),
                ])
                ->create();
        });
    }
}
