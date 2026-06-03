<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Capell\LayoutBuilder\Support\LayoutClipboard;

it('stores the current layout fragment in memory and session', function (): void {
    session()->flush();

    $fragment = clipboardFragment('hero');

    $clipboard = new LayoutClipboard;
    $clipboard->copy($fragment);

    expect($clipboard->hasFragment())->toBeTrue()
        ->and($clipboard->current())->toBe($fragment)
        ->and((new LayoutClipboard)->current())->toEqual($fragment);
});

it('ignores invalid session clipboard payloads', function (): void {
    session()->put('capell.layout-builder.clipboard', ['not' => 'a fragment']);

    expect((new LayoutClipboard)->hasFragment())->toBeFalse();
});

function clipboardFragment(string $key): LayoutFragmentData
{
    return new LayoutFragmentData(
        sourceContainerKey: $key,
        sourceWidgetIndex: null,
        container: ['key' => $key],
        widget: null,
    );
}
