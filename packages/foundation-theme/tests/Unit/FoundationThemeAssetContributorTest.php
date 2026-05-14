<?php

declare(strict_types=1);

use Capell\FoundationTheme\Support\Assets\FoundationThemeAssetContributor;
use Capell\Frontend\Data\FrontendAssetContextData;
use Capell\Frontend\Data\FrontendAssetRequirementData;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;

it('declares only the foundation css asset for blade only pages', function (): void {
    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]->kind)->toBe(FrontendAssetRequirementData::KIND_CSS)
        ->and($requirements[0]->source)->toBe('resources/css/capell/frontend.css');
});

it('declares runtime javascript only when the frontend runtime needs javascript', function (): void {
    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::FullLivewire),
    ));

    expect(collect($requirements)->contains(
        fn (FrontendAssetRequirementData $requirement): bool => $requirement->handle === 'foundation-theme:runtime'
            && $requirement->kind === FrontendAssetRequirementData::KIND_JS,
    ))->toBeTrue();
});
