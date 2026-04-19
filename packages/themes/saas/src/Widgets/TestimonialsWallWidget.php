<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Widgets;

class TestimonialsWallWidget extends AbstractSaasWidget
{
    public string $name = 'Testimonials Wall';

    public string $description = 'Masonry wall of customer testimonials with avatars, company logos and ratings.';

    public string $view = 'saas::components.testimonials-wall';

    public string $icon = 'heroicon-o-chat-bubble-left-right';

    public array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Loved by teams worldwide'],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'Don\'t take our word for it — hear it from our customers.'],
        ['name' => 'columns', 'label' => 'Columns', 'type' => 'select', 'default' => '3', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
        ['name' => 'testimonials', 'label' => 'Testimonials', 'type' => 'repeater', 'default' => [
            [
                'quote' => 'Capell cut our time-to-ship in half. The developer experience is unmatched.',
                'author' => 'Alex Carter',
                'role' => 'Head of Engineering',
                'company' => 'Northwind',
                'avatar_url' => null,
                'rating' => 5,
            ],
            [
                'quote' => 'We replaced four tools with Capell. The team actually enjoys using it.',
                'author' => 'Priya Patel',
                'role' => 'Product Manager',
                'company' => 'Acme Co.',
                'avatar_url' => null,
                'rating' => 5,
            ],
            [
                'quote' => 'The integration story is phenomenal. Everything just works.',
                'author' => 'Jordan Lee',
                'role' => 'CTO',
                'company' => 'Contoso',
                'avatar_url' => null,
                'rating' => 5,
            ],
        ]],
    ];
}
