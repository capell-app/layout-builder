<?php

declare(strict_types=1);

test('background media component does not declare duplicate style attributes on a div', function (): void {
    $blade = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/media/background.blade.php');

    expect($blade)->toBeString();

    preg_match_all('/<div\b[^>]*>/s', $blade, $divMatches);

    foreach ($divMatches[0] as $divTag) {
        expect(substr_count($divTag, 'style='))->toBeLessThanOrEqual(1);
    }
});
