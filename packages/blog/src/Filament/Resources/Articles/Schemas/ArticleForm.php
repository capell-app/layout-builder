<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Schemas;

use Capell\Admin\Enums\SchemaTypeEnum;
use Capell\Admin\Filament\Components\Forms\Type\TypeSchema;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Filament\Resources\Articles\Schemas\Types\ArticlePageSchema;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ArticleForm implements FormConfigurator
{
    /**
     * @param  class-string<ArticleResource>  $resourceClass
     */
    public static function configure(Schema $schema, string $resourceClass = ArticleResource::class): Schema
    {
        return $schema->components(self::getFormSchema($schema, $resourceClass));
    }

    protected static function getFormSchema(Schema $schema, string $resourceClass): array
    {
        return [
            TypeSchema::make()
                ->columns($schema->getColumns())
                ->schema(
                    function (Get $get, TypeSchema $component, ?Pageable $record) use ($schema, $resourceClass): array {
                        $typeId = $get('type_id') ?? ($record instanceof Pageable ? $record->type_id : null);

                        if ($typeId !== null) {
                            /** @var class-string<Type> $model */
                            $model = CapellCore::getModel(ModelEnum::Type);

                            $admin = $model::query()->where('id', $typeId)->value('admin');
                        } else {
                            $defaultType = Article::getDefaultType($resourceClass);

                            if (! $defaultType instanceof Type) {
                                $defaultType = resolve(BlogCreator::class)->createArticlePageType();
                            }

                            $admin = $defaultType->admin;
                        }

                        $adminSchema = isset($admin['schema']) && filled($admin['schema'])
                            ? $admin['schema']
                            : ArticlePageSchema::getKey();

                        return $component->getTypeSchema($schema, SchemaTypeEnum::Page, name: $adminSchema);
                    },
                ),
        ];
    }
}
