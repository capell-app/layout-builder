<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\FormBuilder\Providers\FormBuilderServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

class FormBuilderTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-form-builder';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FormBuilderServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(FormBuilderServiceProvider::$packageName);
    }
}
