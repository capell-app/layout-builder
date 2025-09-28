<?php

declare(strict_types=1);

namespace Capell\Tests\Layout;

use Capell\Admin\AdminServiceProvider;
use Capell\Blog\BlogServiceProvider;
use Capell\Layout\LayoutServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Support\Filament\AdminPanelProvider;

class LayoutTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        $this->packageMigrations = glob(__DIR__ . '/../../../packages/layout/database/migrations/*.php');

        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LayoutServiceProvider::class,
            BlogServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
        ];
    }
}
