<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Feature;

use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

final class LayoutBuilderUninstalledProviderTest extends AbstractTestCase
{
    public function test_it_does_not_register_page_element_assets_as_cloneable_when_uninstalled(): void
    {
        expect(CapellCore::getCloneableRelations('page'))->not->toContain('elementAssets');
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-layout-builder-uninstalled';
    }

    /**
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

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(LayoutBuilderServiceProvider::$packageName, false);
    }
}
