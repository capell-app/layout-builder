<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\StarterSites\Support\Creator\DemoCreator;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Contracts\Foundation\Application;

it('runs demo command successfully', function (): void {
    $user = User::factory()->create(['email' => 'admin@example.com']);

    app()->bind(DemoCreator::class, function (Application $app, array $params): DemoCreator {
        $mock = Mockery::mock(DemoCreator::class . '[setupRelatedSites,createPage,setupSite,setupMainNavigation,setupFooterNavigation,subFooterNavigation]', [$params['url'], $params['author']]);
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('setupRelatedSites')->andReturnNull();
        $mock->shouldReceive('createPage')->andReturnUsing(fn (): Page => new Page);
        $mock->shouldReceive('setupSite')->andReturnNull();
        $mock->shouldReceive('setupMainNavigation')->andReturnNull();
        $mock->shouldReceive('setupFooterNavigation')->andReturnNull();
        $mock->shouldReceive('subFooterNavigation')->andReturnNull();

        return $mock;
    });

    test()->artisan('capell:admin-demo', [
        '--url' => 'https://example.test',
        '--user' => $user->email,
        '--languages' => 'en,fr',
        '--sites' => 'Main Site,Sub Site',
    ])->assertExitCode(0);
});
