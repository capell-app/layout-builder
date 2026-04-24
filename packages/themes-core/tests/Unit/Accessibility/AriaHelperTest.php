<?php

declare(strict_types=1);

use Capell\Themes\Core\Accessibility\AriaHelper;

test('labelledBy returns correct attribute string', function (): void {
    $helper = new AriaHelper;

    expect($helper->labelledBy('search-label'))->toBe('aria-labelledby="search-label"');
});

test('expanded returns correct boolean attribute', function (): void {
    $helper = new AriaHelper;

    expect($helper->expanded(true))->toBe('aria-expanded="true"');
    expect($helper->expanded(false))->toBe('aria-expanded="false"');
});

test('uniqueId starts with prefix and has length greater than 4', function (): void {
    $helper = new AriaHelper;
    $id = $helper->uniqueId('nav');

    expect($id)->toStartWith('nav-');
    expect(strlen($id))->toBeGreaterThan(4);
});

test('role returns role attribute string', function (): void {
    $helper = new AriaHelper;

    expect($helper->role('navigation'))->toBe('role="navigation"');
});

test('current returns aria-current attribute string', function (): void {
    $helper = new AriaHelper;

    expect($helper->current('page'))->toBe('aria-current="page"');
});

test('describedBy returns aria-describedby attribute string', function (): void {
    $helper = new AriaHelper;

    expect($helper->describedBy('hint-text'))->toBe('aria-describedby="hint-text"');
});

test('hidden returns aria-hidden attribute string', function (): void {
    $helper = new AriaHelper;

    expect($helper->hidden(true))->toBe('aria-hidden="true"');
    expect($helper->hidden(false))->toBe('aria-hidden="false"');
});

test('live returns aria-live attribute string', function (): void {
    $helper = new AriaHelper;

    expect($helper->live('polite'))->toBe('aria-live="polite"');
    expect($helper->live('assertive'))->toBe('aria-live="assertive"');
});

test('controls returns aria-controls attribute string', function (): void {
    $helper = new AriaHelper;

    expect($helper->controls('menu-content'))->toBe('aria-controls="menu-content"');
});
