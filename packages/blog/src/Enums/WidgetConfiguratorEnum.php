<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Filament\Configurators\Widgets\ArticleWidgetConfigurator;
use Capell\Blog\Filament\Configurators\Widgets\RelatedWidgetConfigurator;

enum WidgetConfiguratorEnum: string
{
    case Article = ArticleWidgetConfigurator::class;

    case Related = RelatedWidgetConfigurator::class;
}
