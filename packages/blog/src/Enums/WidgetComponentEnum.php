<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

enum WidgetComponentEnum: string
{
    case Archives = 'capell-blog::widget.page.archives';
    case Article = 'capell-blog::widget.page.article';
    case PageRelated = 'capell-blog::widget.page.related';
    case Tags = 'capell-blog::widget.tag.tags';
}
