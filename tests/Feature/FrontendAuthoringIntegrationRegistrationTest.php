<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Bridges\FrontendAuthoring\LayoutBuilderEditableRegionContributor;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;

/**
 * @return list<class-string>
 */
function layoutBuilderEditableRegionContributors(bool $available): array
{
    CapellCore::partialMock()
        ->shouldReceive('isPackageAvailable')
        ->with('capell-app/frontend-authoring')
        ->andReturn($available);
    CapellCore::shouldReceive('isPackageAvailable')->passthru();

    $previous = Container::getInstance();
    $container = new Application(base_path());

    try {
        $provider = new LayoutBuilderServiceProvider($container);
        (fn () => $this->registerFrontendAuthoringIntegration())->call($provider);

        return array_map(static fn (object $contributor): string => $contributor::class, iterator_to_array($container->tagged('capell-frontend-authoring:editable-regions'), false));
    } finally {
        Container::setInstance($previous);
    }
}

it('adds no editable regions when Frontend Authoring is not installed', function (): void {
    expect(layoutBuilderEditableRegionContributors(false))->toBe([]);
});

it('adds Layout Builder editable regions when Frontend Authoring is installed', function (): void {
    expect(layoutBuilderEditableRegionContributors(true))->toBe([LayoutBuilderEditableRegionContributor::class]);
});
