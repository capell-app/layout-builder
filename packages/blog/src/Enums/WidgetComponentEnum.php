<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

enum WidgetComponentEnum: string
{
    case BlogPage = 'capell-blog::livewire.page.blog';

    case PageRelated = 'capell-blog::widget.pages.related';
}
