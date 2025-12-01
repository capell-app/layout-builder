<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Filament\Resources\Widgets\Schemas\Types\ArticleWidgetSchema;
use Capell\Blog\Filament\Resources\Widgets\Schemas\Types\RelatedWidgetSchema;

enum WidgetSchemaEnum: string
{
    case Article = ArticleWidgetSchema::class;

    case Related = RelatedWidgetSchema::class;
}
