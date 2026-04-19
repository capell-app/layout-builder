<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Widgets\BlogListingWidget;

test('blog listing widget declares limit and posts fields', function () {
    $widget = BlogListingWidget::make();

    expect($widget->fieldNames())->toContain('limit', 'posts')
        ->and($widget->view)->toBe('corporate::components.blog-listing');
});
