<?php

declare(strict_types=1);

test('default theme escapes site titles and plain footer text', function (): void {
    $themePath = dirname(__DIR__, 2);

    $header = file_get_contents($themePath . '/resources/views/components/header/index.blade.php');
    $footer = file_get_contents($themePath . '/resources/views/components/footer/index.blade.php');
    $relatedSites = file_get_contents($themePath . '/resources/views/components/footer/related-sites.blade.php');
    $siteInfo = file_get_contents($themePath . '/resources/views/components/footer/site-info.blade.php');

    expect($header)->not->toContain('{!! $site->translation->title !!}');
    expect($siteInfo)->not->toContain('{!! $site->translation->title !!}');
    expect($relatedSites)->not->toContain('{!! $relatedSite->translation->title !!}');
    expect($relatedSites)->not->toContain('{!! $description !!}');
    expect($footer)->not->toContain('{!!' . PHP_EOL . '                Lang::get($footerCopy');
});
