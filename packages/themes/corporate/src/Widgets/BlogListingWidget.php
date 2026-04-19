<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Widgets;

class BlogListingWidget extends AbstractCorporateWidget
{
    public string $name = 'Blog Listing';

    public string $description = 'Responsive grid of recent blog posts with hero image and excerpt.';

    public string $view = 'corporate::components.blog-listing';

    public string $icon = 'heroicon-o-newspaper';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'From our blog'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => 'Ideas, tutorials and case studies.'],
        ['name' => 'limit', 'label' => 'Number of posts', 'type' => 'number', 'default' => 6],
        ['name' => 'posts', 'label' => 'Posts (seed data)', 'type' => 'repeater', 'default' => []],
    ];
}
