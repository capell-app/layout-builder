<?php

declare(strict_types=1);

namespace Capell\Blog\Database\Factories;

use Capell\Admin\Enums\ContentEditorEnum;
use Capell\Admin\Filament\Schemas\Type\PageTypeSchema;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Filament\Schemas\Page\ArticlePageSchema;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Models\Page;
use Capell\Core\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class ArticlePageFactory extends PageFactory
{
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
                        'resource' => BlogResourceEnum::Article->name,
                        'with_tags' => true,
                        'exclude' => true,
                    ],
                ]),
            'parent_id' => null,
        ];
    }

    public function article(?Page $parent = null): self
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent?->getKey(),
        ]);
    }
}
