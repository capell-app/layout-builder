<?php

declare(strict_types=1);

use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Hero\Actions\AddHeroToLayoutAction;
use Capell\Layout\Models\Widget;
use Illuminate\Console\Command;

it('adds hero meta to blog and article pages when blog package is installed', function (): void {
    $mock = AddHeroToLayoutAction::mock();
    $mock->shouldReceive('handle');

    app()->instance(AddHeroToLayoutAction::class, $mock);

    // Simulate blog package installed
    CapellCore::forcePackageInstalled(BlogServiceProvider::$packageName);

    // Arrange: create a site, homepage, blog page, article page, and translations
    $languages = Language::factory(2)->create();
    $site = Site::factory()
        ->language($languages[0])
        ->state(['name' => 'DemoSite'])
        ->withTranslations($languages)
        ->create();

    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->setup($site);

    // Act: run the command
    $this->artisan('capell:hero-demo')
        ->expectsQuestion('Choose the demo content?', ['DemoSite'])
        ->expectsOutput('Demo hero content has been successfully created for site: DemoSite')
        ->expectsOutput('Hero demo content inserted successfully.')
        ->assertExitCode(Command::SUCCESS);

    expect(Widget::query()->firstWhere('key', 'hero'))->not()->toBeNull();
});
