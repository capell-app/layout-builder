<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Health\AbstractWidgetExtensionHealthCheck;

final class ExampleWidgetHealthCheck extends AbstractWidgetExtensionHealthCheck
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }

    protected static function definitionKey(): string
    {
        return 'capell-app.slideshow';
    }

    protected static function filamentWidget(): string
    {
        return ExampleFilamentWidget::class;
    }

    protected static function fallbackView(): string
    {
        return 'widget-health-test::widget';
    }

    protected static function resourceGroups(): array
    {
        return ['capell-app.widget-slideshow'];
    }
}
