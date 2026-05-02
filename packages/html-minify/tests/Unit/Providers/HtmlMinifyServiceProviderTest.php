<?php

declare(strict_types=1);

use Capell\Frontend\Contracts\HtmlMinifier;
use Capell\HtmlMinify\Http\Middleware\HtmlMinifyMiddleware;
use Capell\HtmlMinify\Support\Html\HtmlMinifier as VokuHtmlMinifier;
use Illuminate\Support\Facades\Route;

it('registers the frontend minify middleware alias', function (): void {
    expect(Route::getMiddleware()['frontend.minify'] ?? null)->toBe(HtmlMinifyMiddleware::class);
});

it('binds the frontend html minifier contract', function (): void {
    expect(resolve(HtmlMinifier::class))->toBeInstanceOf(VokuHtmlMinifier::class);
});
