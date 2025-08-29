<?php

declare(strict_types=1);

namespace Capell\Blog\Database\Factories;

use Capell\Admin\Filament\Forms\Type\PageTypeSchema;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Filament\Schemas\Page\ArticlePageForm;
use Capell\Core\Database\Factories\TypeFactory;
use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class ArticleTypeFactory extends TypeFactory
{
    public function article(): self
    {
        return $this->page()
            ->group(BlogTypeGroupEnum::Article->value)
            ->set(
                'admin',
                [
                    'type_schema' => PageTypeSchema::getKey(),
                    'schema' => ArticlePageForm::getKey(),
                    'resource' => BlogResourceEnum::Article->name,
                ]
            );
    }
}
