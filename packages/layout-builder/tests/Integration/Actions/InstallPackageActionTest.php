<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Actions\InstallPackageAction;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
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
        WidgetTypeEnum::Sections->value,
        WidgetTypeEnum::Default->value,
        WidgetTypeEnum::SectionBuilder->value,
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
        ->and(Relation::getMorphedModel('widget_asset'))
        ->toBe(WidgetAsset::class);
});
