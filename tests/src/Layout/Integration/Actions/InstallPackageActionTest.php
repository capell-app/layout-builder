<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Type;
use Capell\Layout\Actions\InstallPackageAction;
use Capell\Layout\Enums\ContentTypeEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Models\Collection;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Relations\Relation;

it('installs layout package: creates types, widgets, layouts, and registers morphs', function (): void {
    InstallPackageAction::run();

    // Layouts created
    $layoutKeys = Layout::query()->pluck('key')->all();
    expect($layoutKeys)
        ->toContain(LayoutEnum::Default->value)
        ->and($layoutKeys)->toContain(LayoutEnum::Home->value)
        ->and($layoutKeys)->toContain(LayoutEnum::Results->value);

    // Widget types created
    $expectedWidgetTypeKeys = [
        WidgetTypeEnum::Contents->value,
        WidgetTypeEnum::Default->value,
        WidgetTypeEnum::ContentBuilder->value,
        WidgetTypeEnum::Media->value,
        WidgetTypeEnum::Navigation->value,
        WidgetTypeEnum::PageContents->value,
        WidgetTypeEnum::Results->value,
        WidgetTypeEnum::Pages->value,
        WidgetTypeEnum::Assets->value,
        WidgetTypeEnum::System->value,
    ];

    $widgetTypeKeys = Type::query()
        ->where('type', LayoutTypeEnum::Widget->value)
        ->pluck('key')
        ->all();

    foreach ($expectedWidgetTypeKeys as $key) {
        expect($widgetTypeKeys)->toContain($key);
    }

    // Content types created (default + builder)
    $contentTypeKeys = Type::query()
        ->where('type', LayoutTypeEnum::Content->value)
        ->pluck('key')
        ->all();

    expect($contentTypeKeys)
        ->toContain(ContentTypeEnum::Default->value)
        ->and($contentTypeKeys)->toContain(ContentTypeEnum::Builder->value);

    $defaultContentType = Type::query()
        ->where('type', LayoutTypeEnum::Content->value)
        ->where('key', ContentTypeEnum::Default->value)
        ->first();

    expect($defaultContentType)
        ->not()->toBeNull()
        ->and($defaultContentType->default)->toBeTrue();

    // Widgets created
    $expectedWidgetKeys = [
        'breadcrumbs',
        'children',
        'assets',
        'gallery',
        'latest-pages',
        'media-carousel',
        'page-content',
        'page-slot',
        'pages-card',
        'siblings',
    ];

    $widgetKeys = Widget::query()->pluck('key')->all();

    foreach ($expectedWidgetKeys as $key) {
        expect($widgetKeys)->toContain($key);
    }

    // Morph maps registered
    expect(Relation::getMorphedModel('widget'))
        ->toBe(Widget::class)
        ->and(Relation::getMorphedModel('content'))
        ->toBe(Collection::class)
        ->and(Relation::getMorphedModel('widget_asset'))
        ->toBe(WidgetAsset::class);
});
