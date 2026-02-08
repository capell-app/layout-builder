<?php

declare(strict_types=1);

use Capell\Blog\Actions\InstallPackageAction;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\WidgetTypeEnum;

it('installs blog package: creates widgets, layouts, updates containers, and seeds site content', function (): void {
    $site = Site::factory()->create();

    // Seed base layouts that InstallPackageAction expects to exist
    $defaultLayout = Layout::factory()->create([
        'key' => 'default',
        'containers' => [
            'main' => [
                'widgets' => [],
            ],
            'sidebar' => [
                'widgets' => [
                    ['widget_key' => 'latest-pages'],
                ],
            ],
        ],
    ]);

    $resultsLayout = Layout::factory()->create([
        'key' => 'results',
        'containers' => [
            'main' => [
                'widgets' => [],
            ],
            'sidebar' => [
                'widgets' => [
                    ['widget_key' => 'latest-pages'],
                ],
            ],
        ],
    ]);

    // Ensure required widget types exist so widgets can be created
    Type::factory()->create(['key' => WidgetTypeEnum::Results->value, 'type' => LayoutTypeEnum::Widget->value]);
    Type::factory()->create(['key' => WidgetTypeEnum::System->value, 'type' => LayoutTypeEnum::Widget->value]);

    InstallPackageAction::run();

    // Widgets created globally
    $widgetKeys = ['article', 'latest-articles', 'archives', 'tags', 'related-pages'];

    foreach ($widgetKeys as $key) {
        expect(\Capell\Layout\Models\Widget::query()->where('key', $key)->exists())
            ->toBeTrue();
    }

    // Layouts created by the blog package
    $createdLayouts = Layout::query()->whereIn('key', ['article', 'archives', 'blog-results', 'tags'])->pluck('key')->all();
    expect($createdLayouts)
        ->toContain('article')
        ->and($createdLayouts)->toContain('archives')
        ->and($createdLayouts)->toContain('blog-results')
        ->and($createdLayouts)->toContain('tags');

    // Default and Results layouts sidebar gets latest-articles widget appended and latest-pages removed
    $defaultLayout->refresh();
    $resultsLayout->refresh();

    $defaultSidebarWidgets = array_column($defaultLayout->containers['sidebar']['widgets'] ?? [], 'widget_key');
    $resultsSidebarWidgets = array_column($resultsLayout->containers['sidebar']['widgets'] ?? [], 'widget_key');

    expect($defaultSidebarWidgets)
        ->not()->toContain('latest-pages')
        ->and($defaultSidebarWidgets)->toContain('latest-articles');

    expect($resultsSidebarWidgets)
        ->not()->toContain('latest-pages')
        ->and($resultsSidebarWidgets)->toContain('latest-articles');

    // Site content gets seeded (blog pages created)
    $blogType = Type::query()->where('key', 'blog')->pageType()->first();
    $blogPage = $blogType ? $site->pages()->where('type_id', $blogType->id)->first() : null;

    expect($blogPage)->not()->toBeNull();
});
