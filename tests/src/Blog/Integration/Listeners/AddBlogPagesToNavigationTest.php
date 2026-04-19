<?php

declare(strict_types=1);

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Listeners\AddBlogPagesToNavigation;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Enums\NavigationHandle;
use Capell\Core\Events\NavigationCreating;
use Capell\Core\Models\Navigation;
use Capell\Core\Models\Site;

beforeEach(function (): void {
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();
});

it('adds blog page to main navigation', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();

    $navigation = Navigation::factory()
        ->site($site)
        ->create(['key' => NavigationHandle::Main->value]);

    $event = new NavigationCreating($navigation);

    (new AddBlogPagesToNavigation)->handle($event);

    $blogPageInNav = $navigation->items()
        ->whereHas('page', function ($query): void {
            $query->whereHas('type', function ($q): void {
                $q->where('key', BlogPageTypeEnum::Blog->value);
            });
        })
        ->exists();

    expect($blogPageInNav)->toBeTrue();
});

it('adds blog page to footer navigation', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $navigation = Navigation::factory()
        ->site($site)
        ->create(['key' => NavigationHandle::Footer->value]);

    $event = new NavigationCreating($navigation);

    (new AddBlogPagesToNavigation)->handle($event);

    $blogPageInNav = $navigation->items()
        ->whereHas('page', function ($query): void {
            $query->whereHas('type', function ($q): void {
                $q->where('key', BlogPageTypeEnum::Blog->value);
            });
        })
        ->exists();

    expect($blogPageInNav)->toBeTrue();
});

it('ignores non-main and non-footer navigations', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $navigation = Navigation::factory()
        ->site($site)
        ->create(['key' => 'custom']);

    $event = new NavigationCreating($navigation);

    (new AddBlogPagesToNavigation)->handle($event);

    expect($navigation->items()->count())->toBe(0);
});
