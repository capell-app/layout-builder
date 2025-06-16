<?php

declare(strict_types=1);

namespace Capell\Tests\Blog;

use Capell\Admin\AdminServiceProvider;
use Capell\Blog\BlogServiceProvider;
use Capell\Frontend\FrontendServiceProvider;
use Capell\Tests\packages\AbstractTestCase;
use Capell\Tests\Support\Filament\AdminPanelProvider;

class BlogTestCase extends AbstractTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            BlogServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }
}
