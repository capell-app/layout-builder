<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests;

use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

abstract class LayoutBuilderTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-layout-builder';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LayoutBuilderServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}
