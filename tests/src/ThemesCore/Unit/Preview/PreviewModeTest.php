<?php

declare(strict_types=1);

use Capell\Themes\Core\Preview\PreviewMode;

test('generateToken returns a non-empty string', function (): void {
    $preview = new PreviewMode(secretKey: 'my-app-key');
    $token = $preview->generateToken('/page/my-draft');

    expect($token)->toBeString()->not->toBeEmpty();
});

test('validateToken returns true for a fresh token with correct path', function (): void {
    $preview = new PreviewMode(secretKey: 'my-app-key');
    $token = $preview->generateToken('/page/my-draft', expiresInMinutes: 60);

    expect($preview->validateToken($token, '/page/my-draft'))->toBeTrue();
});

test('validateToken returns false for wrong path', function (): void {
    $preview = new PreviewMode(secretKey: 'my-app-key');
    $token = $preview->generateToken('/page/my-draft', expiresInMinutes: 60);

    expect($preview->validateToken($token, '/page/other-page'))->toBeFalse();
});

test('isExpired returns false for a fresh token', function (): void {
    $preview = new PreviewMode(secretKey: 'my-app-key');
    $token = $preview->generateToken('/page/my-draft', expiresInMinutes: 60);

    expect($preview->isExpired($token))->toBeFalse();
});

test('signedUrl contains the token as a query param', function (): void {
    $preview = new PreviewMode(secretKey: 'my-app-key');
    $url = $preview->signedUrl('/page/my-draft', baseUrl: 'https://example.com', expiresInMinutes: 60);

    expect($url)->toContain('preview_token=');
    expect($url)->toStartWith('https://example.com/page/my-draft?');
});

test('a token with negative expiry is immediately expired', function (): void {
    $preview = new PreviewMode(secretKey: 'my-app-key');
    $token = $preview->generateToken('/page/my-draft', expiresInMinutes: -1);

    expect($preview->isExpired($token))->toBeTrue();
    expect($preview->validateToken($token, '/page/my-draft'))->toBeFalse();
});
