<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\SaasFooterWidget;

test('saas-footer widget has expected metadata', function (): void {
    $widget = SaasFooterWidget::make();

    expect($widget->name)->toBe('SaaS Footer')
        ->and($widget->view)->toBe('saas::components.saas-footer')
        ->and($widget->fieldNames())->toContain('brand', 'columns', 'socials');
});

test('saas-footer defaults include product/company/resources/legal columns', function (): void {
    $widget = SaasFooterWidget::make();
    $columns = $widget->defaults()['columns'];
    $headings = array_map(static fn (array $c) => $c['heading'] ?? '', $columns);

    expect($headings)->toContain('Product', 'Company', 'Resources', 'Legal');
});
