<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Actions\PersistRenderProfileAction;
use Capell\FrontendOptimizer\Actions\ResolveRenderProfileAction;
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\AssetSlot;
use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;
use Capell\FrontendOptimizer\Support\PlaywrightCriticalCssGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('writes a playwright payload with only critical-eligible stylesheet paths', function (): void {
    Storage::disk('local')->deleteDirectory('capell/frontend-optimizer');
    Storage::disk('local')->deleteDirectory('frontend-optimizer-test');

    $scriptPath = storage_path('app/frontend-optimizer-test/capture-payload.mjs');
    File::ensureDirectoryExists(dirname($scriptPath));
    File::put($scriptPath, <<<'JS'
import fs from 'node:fs/promises';

const [payloadPath, outputPath] = process.argv.slice(2);
const payload = JSON.parse(await fs.readFile(payloadPath, 'utf8'));
await fs.mkdir(new URL('.', `file://${outputPath}`).pathname, { recursive: true });
await fs.writeFile(outputPath, '.hero { display: grid; }\n');
await fs.writeFile(`${outputPath}.payload.json`, JSON.stringify(payload));
JS);

    config()->set('capell-frontend-optimizer.playwright.script', $scriptPath);

    $profileData = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [
            FrontendAssetSet::make()
                ->css(
                    handle: 'hero',
                    path: '/build/hero.css',
                    loadingStrategy: AssetLoadingStrategy::Critical,
                    slot: AssetSlot::AboveFold,
                    criticalEligible: true,
                )
                ->css(
                    handle: 'footer',
                    path: '/build/footer.css',
                    loadingStrategy: AssetLoadingStrategy::Lazy,
                    slot: AssetSlot::BelowFold,
                )
                ->js('carousel', '/build/carousel.js', AssetLoadingStrategy::Deferred),
        ],
    );
    $profile = PersistRenderProfileAction::run($profileData);

    $criticalCssPath = resolve(PlaywrightCriticalCssGenerator::class)->generate($profile, 'https://example.test');
    $payload = json_decode(
        Storage::disk('local')->get($criticalCssPath . '.payload.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($payload['eligible_stylesheet_paths'])->toBe(['/build/hero.css']);
});
