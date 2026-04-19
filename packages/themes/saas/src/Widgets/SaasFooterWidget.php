<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Widgets;

class SaasFooterWidget extends AbstractSaasWidget
{
    public string $name = 'SaaS Footer';

    public string $description = 'Large multi-column footer with product, company, resources and legal links plus socials.';

    public string $view = 'saas::components.saas-footer';

    public string $icon = 'heroicon-o-bars-3-bottom-right';

    public array $fields = [];

    public function __construct()
    {
        $this->fields = [
            ['name' => 'brand', 'label' => 'Brand', 'type' => 'text', 'default' => 'Capell'],
            ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'default' => 'The all-in-one platform for modern product teams.'],
            ['name' => 'copyright', 'label' => 'Copyright', 'type' => 'text', 'default' => '(c) '.date('Y').' Capell. All rights reserved.'],
            ['name' => 'show_newsletter', 'label' => 'Show newsletter', 'type' => 'select', 'default' => 'yes', 'options' => ['yes' => 'Yes', 'no' => 'No']],
            ['name' => 'columns', 'label' => 'Link columns', 'type' => 'repeater', 'default' => [
                ['heading' => 'Product', 'links' => [
                    ['label' => 'Features', 'url' => '#features'],
                    ['label' => 'Pricing', 'url' => '#pricing'],
                    ['label' => 'Integrations', 'url' => '#integrations'],
                    ['label' => 'Changelog', 'url' => '/changelog'],
                ]],
                ['heading' => 'Company', 'links' => [
                    ['label' => 'About', 'url' => '/about'],
                    ['label' => 'Customers', 'url' => '/customers'],
                    ['label' => 'Careers', 'url' => '/careers'],
                    ['label' => 'Contact', 'url' => '/contact'],
                ]],
                ['heading' => 'Resources', 'links' => [
                    ['label' => 'Documentation', 'url' => '/docs'],
                    ['label' => 'API reference', 'url' => '/api'],
                    ['label' => 'Blog', 'url' => '/blog'],
                    ['label' => 'Community', 'url' => '/community'],
                ]],
                ['heading' => 'Legal', 'links' => [
                    ['label' => 'Privacy policy', 'url' => '/privacy'],
                    ['label' => 'Terms of service', 'url' => '/terms'],
                    ['label' => 'Security', 'url' => '/security'],
                    ['label' => 'DPA', 'url' => '/dpa'],
                ]],
            ]],
            ['name' => 'socials', 'label' => 'Social links', 'type' => 'repeater', 'default' => [
                ['label' => 'Twitter', 'url' => '#'],
                ['label' => 'LinkedIn', 'url' => '#'],
                ['label' => 'GitHub', 'url' => '#'],
                ['label' => 'YouTube', 'url' => '#'],
            ]],
        ];
    }
}
