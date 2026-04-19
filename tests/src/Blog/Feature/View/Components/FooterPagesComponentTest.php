<?php

declare(strict_types=1);

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\View\Components\Footer\Pages;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\View\View;

uses(TestingFrontend::class);

beforeEach(function (): void {
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();
});

it('renders pages footer component with blog pages', function (): void {
    $site = Site::factory()->create();

    $blogPage = Page::factory()
        ->site($site)
        ->create();

    $blogPage->type()->associate(
        $site->workspace->types()
            ->where('key', BlogPageTypeEnum::Blog->value)
            ->first(),
    )->save();

    $component = new Pages;

    $view = $component->render();

    expect($view)->toBeInstanceOf(View::class);
});
