<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Enums\MetaSchemaEnum;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('can see all meta schema', function (): void {
    $site = Site::factory()
        ->withTranslations()
        ->meta([
            'meta_schema' => array_values(MetaSchemaEnum::getComponents()),
            'business_name' => 'Test Business',
        ])
        ->create();

    $page = Page::factory()
        ->site($site)
        ->withTranslations()
        ->has(Media::factory()->count(2)->video())
        ->has(Media::factory()->image())
        ->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'script[type="application/ld+json"]',
            fn (AssertElement $elm): BaseAssert => $elm->containsText('Test Business'),
        );
});

test('can see website meta schema', function (): void {
    $site = Site::factory()
        ->withTranslations()
        ->meta([
            'meta_schema' => [
                MetaSchemaEnum::Website->getComponent(),
            ],
            'business_name' => 'Test Business',
        ])
        ->create();

    $page = Page::factory()
        ->site($site)
        ->withTranslations()
        ->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'script[type="application/ld+json"]',
            fn (AssertElement $elm): BaseAssert => $elm->containsText('Test Business'),
        );
});

test('can see webpage meta schema', function (): void {
    $site = Site::factory()
        ->withTranslations()
        ->meta([
            'meta_schema' => [
                MetaSchemaEnum::Webpage->getComponent(),
            ],
            'business_name' => 'Test Business',
        ])
        ->create();

    $page = Page::factory()
        ->site($site)
        ->withTranslations(data: [
            'meta' => [
                'keywords' => 'test, keywords',
                'description' => 'test description',
            ],
        ])
        ->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'script[type="application/ld+json"]',
            function (AssertElement $elm) use ($page): BaseAssert {
                $json = $elm->getParser()->getText();
                $data = json_decode($json, true);

                expect($data)->toBeArray()
                    ->and($data['@context'] ?? null)->toBe('https://schema.org')
                    ->and($data['@type'] ?? null)->toBe('WebPage')
                    ->and($data['dateCreated'] ?? null)->toBe($page->created_at?->toDateString())
                    ->and($data['dateModified'] ?? null)->toBe($page->updated_at?->toDateString())
                    ->and($data['url'] ?? null)->toBe($page->pageUrl->full_url)
                    ->and($data['name'] ?? null)->toBe($page->translation->label)
                    ->and($data['headline'] ?? null)->toBe($page->translation->title)
                    ->and($data['availableLanguage'] ?? null)->not()->toBeNull()
                    ->and($data['keywords'] ?? null)->toBe($page->translation->meta_keywords)
                    ->and($data['description'] ?? null)->toBe($page->translation->meta_description);

                return $elm;
            },
        );
});
