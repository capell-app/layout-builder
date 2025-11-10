<?php

declare(strict_types=1);

namespace Capell\Tests\Blog;

use Capell\Admin\AdminServiceProvider;
use Capell\Blog\BlogServiceProvider;
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

    protected function getPackageName(): string
    {
        return 'blog';
    }
}
