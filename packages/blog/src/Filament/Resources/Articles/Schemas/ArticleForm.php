<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\Blog\Filament\Configurators\Articles\ArticlePageConfigurator;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Type;
use Filament\Schemas\Schema;

class ArticleForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $resourceClass = ArticleResource::class;
        $resolver = resolve(ConfiguratorResolver::class);
        $record = $configurator->getRecord();

        if ($record instanceof Pageable && $record->type_id !== null) {
            /** @var class-string<Type> $model */
            $model = Type::class;

            $type = $model::query()->find($record->type_id);
            $adminType = $type instanceof Type
                ? $resolver->resolveForType($type, ConfiguratorTypeEnum::Page, ArticlePageConfigurator::getKey())
                : ArticlePageConfigurator::class;

            if (method_exists($record, 'type')) {
                $record->loadMissing('type');
            }

            return $adminType::configure($configurator, ConfiguratorContextData::forEdit(ConfiguratorTypeEnum::Page));
        }

        $defaultType = Article::getDefaultType($resourceClass);

        if (! $defaultType instanceof Type) {
            $defaultType = resolve(BlogCreator::class)->createArticlePageType();
        }

        $adminType = $resolver->resolveForType($defaultType, ConfiguratorTypeEnum::Page, ArticlePageConfigurator::getKey());
        $operation = $configurator->getOperation();

        return $adminType::configure($configurator, new ConfiguratorContextData(
            ConfiguratorTypeEnum::Page,
            in_array($operation, ['create', 'createOption', 'edit', 'editOption', 'replicate'], true) ? $operation : 'create',
            $defaultType->key,
        ));
    }
}
