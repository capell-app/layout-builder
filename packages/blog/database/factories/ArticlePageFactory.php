<?php

declare(strict_types=1);

namespace Capell\Blog\Database\Factories;

use Capell\Admin\Enums\ContentEditorEnum;
use Capell\Admin\Filament\Resources\Types\Schemas\Types\PageTypeSchema;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Resources\Articles\Schemas\Types\ArticlePageSchema;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticlePageFactory extends PageFactory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            ...parent::definition(),
            'type_id' => fn () => Type::factory()
                ->page()
                ->state([
                    'group' => BlogTypeGroupEnum::Article->value,
                    'admin' => [
                        'content_editor' => ContentEditorEnum::RichEditor->value,
                        'icon' => 'heroicon-o-newspaper',
                        'type_schema' => PageTypeSchema::getKey(),
                        'schema' => ArticlePageSchema::getKey(),
                        'resource' => strtolower(ResourceEnum::Article->name),
                        'exclude' => true,
                    ],
                ]),
            'parent_id' => null,
        ];
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
