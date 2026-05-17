<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;

it('registers the main layout area by default', function (): void {
    $registry = new LayoutAreaRegistry;

    expect($registry->options())->toHaveKey(LayoutAreaRegistry::MAIN)
        ->and($registry->label(LayoutAreaRegistry::MAIN))->toBe(__('capell-layout-builder::generic.main_content_area'));
});

it('replaces duplicate area registrations within the same scope', function (): void {
    $registry = new LayoutAreaRegistry;

    $registry->register('header', 'Header');
    $registry->register('header', 'Site header');

    expect($registry->options()['header'])->toBe('Site header');
});

it('filters theme scoped areas by active theme key', function (): void {
    $registry = new LayoutAreaRegistry;

    $registry->register('announcement', 'Announcement', themeKey: 'saas');
    $registry->register('header', 'Global header');
    $registry->register('header', 'SaaS header', themeKey: 'saas');

    expect($registry->options('agency'))->toHaveKey('header')
        ->and($registry->options('agency'))->not->toHaveKey('announcement')
        ->and($registry->options('agency')['header'])->toBe('Global header')
        ->and($registry->options('saas'))->toHaveKey('announcement')
        ->and($registry->options('saas')['header'])->toBe('SaaS header');
});

it('treats containers without an area as main content', function (): void {
    $registry = new LayoutAreaRegistry;

    expect($registry->containerArea(['meta' => []]))->toBe(LayoutAreaRegistry::MAIN)
        ->and($registry->containerArea(['meta' => ['area' => 'header']]))->toBe('header');
});
