<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Actions\ResolveRenderProfileAction;
use Capell\FrontendOptimizer\Actions\StoreRenderProfileManifestAction;
use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;
use Illuminate\Support\Facades\Storage;

it('stores render profile manifests on the local disk', function (): void {
    Storage::fake('local');

    $profile = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [FrontendAssetSet::make()->css('base', 'base.css')],
    );

    $path = StoreRenderProfileManifestAction::run($profile);

    Storage::disk('local')->assertExists($path);

    expect(json_decode((string) Storage::disk('local')->get($path), true, flags: JSON_THROW_ON_ERROR))
        ->toMatchArray([
            'hash' => $profile->hash,
            'scope' => 'layout',
        ]);
});
