<?php

declare(strict_types=1);

use Capell\Themes\Core\Data\ThemeSettings;
use Capell\Themes\Core\Http\ThemeTokensController;
use Symfony\Component\HttpFoundation\Response;

test('toCss() includes primary and accent color tokens', function (): void {
    $settings = new ThemeSettings(
        active_theme: 'corporate',
        primary_color: '#1a2d6d',
        accent_color: '#f59e0b',
    );
    $controller = new ThemeTokensController($settings);
    $css = $controller->toCss();

    expect($css)->toContain('--color-primary: #1a2d6d');
    expect($css)->toContain('--color-accent: #f59e0b');
});

test('toCss() wraps tokens in :root block', function (): void {
    $settings = new ThemeSettings(active_theme: 'corporate');
    $controller = new ThemeTokensController($settings);

    expect($controller->toCss())->toStartWith(':root {');
});

test('render() returns a Response with text/css content type', function (): void {
    $settings = new ThemeSettings(active_theme: 'agency');
    $controller = new ThemeTokensController($settings);
    $response = $controller->render();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toContain('text/css');
});
