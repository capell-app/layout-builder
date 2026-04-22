<?php

declare(strict_types=1);

namespace Capell\Blog\Database\Factories;

use Capell\Admin\Filament\Schemas\Types\PageTypeSchema;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Schemas\Articles\ArticlePageSchema;
use Capell\Core\Database\Factories\TypeFactory;
use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class ArticleTypeFactory extends TypeFactory
{
    public function article(): TypeFactory
    {
        return $this->page()
            ->group(BlogTypeGroupEnum::Article->value)
            ->set(
                'admin',
                [
                    'type_schema' => PageTypeSchema::getKey(),
                    'schema' => ArticlePageSchema::getKey(),
                    'resource' => strtolower(ResourceEnum::Article->name),
                ],
            );
    }
}
