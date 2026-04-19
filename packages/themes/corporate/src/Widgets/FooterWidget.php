<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Widgets;

use Illuminate\Support\Facades\Date;

class FooterWidget extends AbstractCorporateWidget
{
    public string $name = 'Footer';

    public string $description = 'Site footer with columns of links, newsletter signup and socials.';

    public string $view = 'corporate::components.footer';

    public string $icon = 'heroicon-o-bars-3-bottom-right';

    public array $fields = [];

    public function __construct()
    {
        $this->fields = [
            ['name' => 'layout', 'label' => 'Layout', 'type' => 'select', 'default' => 'expanded', 'options' => ['minimal' => 'Minimal', 'expanded' => 'Expanded', 'newsletter' => 'Newsletter']],
            ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'default' => 'A modern CMS for serious sites.'],
            ['name' => 'copyright', 'label' => 'Copyright', 'type' => 'text', 'default' => '(c) ' . Date::now()->format('Y') . ' Capell. All rights reserved.'],
            ['name' => 'columns', 'label' => 'Link columns', 'type' => 'repeater', 'default' => [
                ['heading' => 'Product', 'links' => [['label' => 'Features', 'url' => '#features'], ['label' => 'Pricing', 'url' => '#pricing']]],
                ['heading' => 'Company', 'links' => [['label' => 'About', 'url' => '/about'], ['label' => 'Contact', 'url' => '/contact']]],
            ]],
        ];
    }
}
