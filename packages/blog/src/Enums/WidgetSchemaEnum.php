<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Filament\Schemas\Widgets\ArticleWidgetSchema;
use Capell\Blog\Filament\Schemas\Widgets\RelatedWidgetSchema;

enum WidgetSchemaEnum: string
{
    case Article = ArticleWidgetSchema::class;

    case Related = RelatedWidgetSchema::class;
}
