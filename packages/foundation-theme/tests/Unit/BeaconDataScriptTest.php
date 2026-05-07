<?php

declare(strict_types=1);

it('boots the beacon on document load and Livewire navigation', function (): void {
    $script = file_get_contents(
        dirname(__DIR__, 2) . '/resources/js/utilities/beacon-data.js',
    );

    expect($script)
        ->toContain("document.readyState === 'loading'")
        ->toContain('DOMContentLoaded')
        ->toContain("document.addEventListener('livewire:navigated', onPageLoad)");
});
