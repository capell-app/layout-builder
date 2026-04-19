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

test('footer declares contentinfo landmark', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/footer.blade.php');
    expect($html)->toContain('role="contentinfo"');
});

test('contact form inputs have labels and required attributes', function () use ($componentsDir) {
    $html = file_get_contents($componentsDir.'/contact-form.blade.php');
    expect($html)->toContain('<label for="corp-name"')
        ->toContain('<label for="corp-email"')
        ->toContain('<label for="corp-message"')
        ->toContain('required');
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
