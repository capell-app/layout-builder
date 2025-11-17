<?php

declare(strict_types=1);

namespace Capell\Tests\Blog;

use Capell\Admin\AdminServiceProvider;
use Capell\Blog\BlogServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Support\Filament\AdminPanelProvider;

class BlogTestCase extends AbstractTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            BlogServiceProvider::class,
            AdminServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    #[\Override]
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(BlogServiceProvider::$packageName);
    }

    protected function getPackageName(): string
    {
        return 'blog';
    }
}
