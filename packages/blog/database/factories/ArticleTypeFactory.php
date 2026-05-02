<?php

declare(strict_types=1);

namespace Capell\Blog\Database\Factories;

use Capell\Admin\Filament\Configurators\Types\PageTypeConfigurator;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Configurators\Articles\ArticlePageConfigurator;
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
                    'type_configurator' => PageTypeConfigurator::getKey(),
                    'configurator' => ArticlePageConfigurator::getKey(),
                    'resource' => strtolower(ResourceEnum::Article->name),
                ],
            );
    }
}
