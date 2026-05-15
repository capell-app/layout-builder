<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Capell\LayoutBuilder\Support\LayoutPresetRepository;

it('stores and finds named layout presets in the session', function (): void {
    session()->flush();

    $repository = new LayoutPresetRepository;
    $fragment = presetFragment('hero');

    $repository->put('Hero preset', 'Reusable hero layout', $fragment);

    expect($repository->find('Hero preset'))->toEqual($fragment)
        ->and($repository->all())->toHaveKey('Hero preset')
        ->and($repository->find('Missing'))->toBeNull();
});

it('ignores invalid preset payloads', function (): void {
    session()->put('capell.layout-builder.presets', [
        'Broken preset' => ['fragment' => ['not' => 'a fragment']],
    ]);

    expect((new LayoutPresetRepository)->find('Broken preset'))->toBeNull();
});

function presetFragment(string $key): LayoutFragmentData
{
    return new LayoutFragmentData(
        sourceContainerKey: $key,
        sourceElementIndex: 0,
        container: null,
        element: ['key' => $key],
    );
}
