<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

class TestimonialsQuoteWidget extends AbstractAgencyWidget
{
    public string $name = 'Testimonials Quote';

    public string $description = 'Large pull-quote testimonials, shown one at a time with client attribution.';

    public string $view = 'agency::components.testimonials-quote';

    public string $icon = 'heroicon-o-chat-bubble-left-right';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Words from the people we work for'],
        ['name' => 'testimonials', 'label' => 'Testimonials', 'type' => 'repeater', 'default' => [
            ['quote' => 'They rebuilt our entire brand in eight weeks. We shipped on time, and the work still feels right two years later.', 'name' => 'Sam Rivers', 'role' => 'VP Marketing, Northwind', 'avatar' => null],
            ['quote' => 'The best agency relationship we\'ve had. No theatre, just exceptional work.', 'name' => 'Priya Okonkwo', 'role' => 'CEO, Parallel', 'avatar' => null],
        ]],
    ];
}
