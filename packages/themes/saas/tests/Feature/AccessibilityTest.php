<?php

declare(strict_types=1);

/*
 | Accessibility smoke tests — inspect the Blade component source for
 | the presence of required accessibility markers. These run without a
 | full Laravel boot so they can execute in package CI quickly.
 */

$componentsDir = __DIR__.'/../../resources/views/components';

test('layout includes skip-to-content link and main landmark', function () use ($componentsDir) {
    $layout = file_get_contents(__DIR__.'/../../resources/views/layouts/app.blade.php');
    expect($layout)->toContain('skip-to-content', '<main')->toContain('role="main"');
});

test('header declares banner and nav landmarks', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/header.blade.php');
    expect($html)->toContain('role="banner"')
        ->toContain('role="navigation"')
        ->toContain('aria-label="Primary"');
});

test('saas footer declares contentinfo landmark', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/saas-footer.blade.php');
    expect($html)->toContain('role="contentinfo"');
});

test('pricing toggle checkbox has an aria-label', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/pricing-table.blade.php');
    expect($html)->toContain('aria-label="Toggle annual billing"');
});

test('faq accordion uses native details/summary', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/faq-accordion.blade.php');
    expect($html)->toContain('<details')
        ->toContain('<summary');
});

test('hero trust badges rendered inside a list', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/hero-with-screenshot.blade.php');
    expect($html)->toContain('role="list"');
});

test('testimonials rating has aria-label with rating value', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/testimonials-wall.blade.php');
    expect($html)->toContain('aria-label="Rating:');
});

test('breadcrumbs set aria-current on last item', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/breadcrumbs.blade.php');
    expect($html)->toContain('aria-current="page"')
        ->toContain('aria-label="Breadcrumb"');
});

test('language switcher has an aria-label', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/language-switcher.blade.php');
    expect($html)->toContain('aria-label="Change language"');
});

test('dark mode toggle is a button with aria-label', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/dark-mode-toggle.blade.php');
    expect($html)->toContain('aria-label="{{ $label }}"')
        ->toContain('type="button"');
});

test('use-cases tabs declare tablist/tab/tabpanel roles', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/use-cases-tabs.blade.php');
    expect($html)->toContain('role="tablist"')
        ->toContain('role="tab"')
        ->toContain('role="tabpanel"');
});

test('css exposes focus-visible outline and reduced-motion block', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/theme.css');
    expect($css)->toContain('focus-visible')
        ->toContain('prefers-reduced-motion')
        ->toContain('.skip-to-content');
});
