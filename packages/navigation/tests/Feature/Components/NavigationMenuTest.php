<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;

it('renders a package menu component with explicit frontend context', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $siteDomain = $site->siteDomains->first();

    Navigation::factory()->create([
        'key' => 'primary',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [
            [
                'label' => 'Company',
                'type' => NavigationItemType::Heading->value,
            ],
            [
                'label' => 'Docs',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/docs'],
            ],
            [
                'label' => 'Hidden',
                'type' => NavigationItemType::Link->value,
                'is_visible' => false,
                'data' => ['url' => '/hidden'],
            ],
        ],
    ]);

    $view = $this->blade(
        '<x-capell-navigation::menu key="primary" :site="$site" :language="$language" :page="$currentPage" :domain="$siteDomain" />',
        ['site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'siteDomain' => $siteDomain],
    );

    $view
        ->assertSee('Company')
        ->assertSee('Docs')
        ->assertSee('href="/docs"', false)
        ->assertDontSee('href=""', false)
        ->assertDontSee('Hidden');
});

it('renders nothing when the package menu component has no matching context', function (): void {
    $this->blade('<x-capell-navigation::menu key="missing" />')
        ->assertDontSee('<nav', false);
});
