<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\View\Components\Widget\Page\RelatedWidget;

enum WidgetComponentEnum: string
{
    case Archives = 'capell-blog::widget.page.archives';
    case Article = 'capell-blog::widget.page.article';
    case PageRelated = 'capell-blog::PageBreadcrumbs.related';
    case Tags = 'capell-blog::widget.tag.tags';

    public static function getComponents(): array
    {
        $components = [];
        foreach (self::cases() as $pageComponent) {
            $components[$pageComponent->value] = $pageComponent->getComponent();
        }

        return $components;
    }

    public function getComponent(): ?string
    {
        return match ($this) {
            self::PageRelated => RelatedWidget::class,
            default => null,
        };
    }
}
