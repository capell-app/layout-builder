<?php

declare(strict_types=1);

use Capell\Themes\Core\Analytics\AnalyticsProvider;
use Capell\Themes\Core\Analytics\GoogleAnalytics4;

test('GoogleAnalytics4 implements AnalyticsProvider', function (): void {
    $ga4 = new GoogleAnalytics4('G-TEST123');
    expect($ga4)->toBeInstanceOf(AnalyticsProvider::class);
});

test('AnalyticsProvider contract declares initScript and enabled', function (): void {
    $methods = get_class_methods(AnalyticsProvider::class);
    expect($methods)->toContain('initScript');
    expect($methods)->toContain('enabled');
});
