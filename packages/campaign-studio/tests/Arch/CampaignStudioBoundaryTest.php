<?php

declare(strict_types=1);

it('declares insights as a required package dependency', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $capellManifestContents = file_get_contents($packagePath . '/capell.json');
    $composerManifestContents = file_get_contents($packagePath . '/composer.json');

    $capellManifest = json_decode(
        $capellManifestContents === false ? '[]' : $capellManifestContents,
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );
    $composerManifest = json_decode(
        $composerManifestContents === false ? '[]' : $composerManifestContents,
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($capellManifest['dependencies']['requires'])->toContain('capell-app/insights')
        ->and($capellManifest['dependencies']['optional'])->not->toContain('capell-app/insights')
        ->and($composerManifest['require'])->toHaveKey('capell-app/insights');
});

arch()
    ->expect('Capell\CampaignStudio')
    ->classes()
    ->toUseStrictEquality();
