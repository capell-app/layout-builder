<?php

declare(strict_types=1);

namespace Capell\Blog\Database\Factories;

use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends PageFactory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            ...parent::definition(),
            'layout_id' => fn (): int => resolve(BlogCreator::class)->createArticleLayout()->id,
            'type_id' => fn (): int => resolve(BlogCreator::class)->createArticlePageType()->id,
            'parent_id' => null,
        ];
    }

    public function publisher(Model $user): self
    {
        return $this->state(fn (): array => [
            'publisher_type' => $user->getMorphClass(),
            'publisher_id' => $user->getKey(),
        ]);
    }

    public function article(?Article $parent = null): self
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent?->getKey(),
        ]);
    }

    public function withTags(): self
    {
        return $this->afterCreating(function (Article $page): void {
            if (Tag::query()->count() < 10) {
                Tag::factory()->count(3)->create();
            }

            $tags = Tag::query()->inRandomOrder()->limit(fake()->numberBetween(1, 3))->get();

            $page->tags()->attach($tags);
        });
    }
}
