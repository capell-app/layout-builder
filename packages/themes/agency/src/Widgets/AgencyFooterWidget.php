<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

class AgencyFooterWidget extends AbstractAgencyWidget
{
    public string $name = 'Agency Footer';

    public string $description = 'Expressive social-first footer with oversized wordmark and contact CTA.';

    public string $view = 'agency::components.agency-footer';

    public string $icon = 'heroicon-o-hashtag';

    public array $fields = [];

    public function __construct()
    {
        $this->fields = [
            ['name' => 'wordmark', 'label' => 'Wordmark', 'type' => 'text', 'default' => 'Capell.'],
            ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'default' => 'Let\'s make something worth looking at.'],
            ['name' => 'cta_label', 'label' => 'CTA label', 'type' => 'text', 'default' => 'Start a project'],
            ['name' => 'cta_url', 'label' => 'CTA URL', 'type' => 'text', 'default' => '#inquiry'],
            ['name' => 'copyright', 'label' => 'Copyright', 'type' => 'text', 'default' => '(c) '.date('Y').' Capell Studio. Made with opinions.'],
            ['name' => 'socials', 'label' => 'Social links', 'type' => 'repeater', 'default' => [
                ['label' => 'Instagram', 'url' => '#'],
                ['label' => 'Dribbble', 'url' => '#'],
                ['label' => 'Behance', 'url' => '#'],
                ['label' => 'LinkedIn', 'url' => '#'],
            ]],
        ];
    }
}
