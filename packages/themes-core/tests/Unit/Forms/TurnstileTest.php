<?php

declare(strict_types=1);

use Capell\Themes\Core\Forms\Turnstile;

test('renderWidget contains the siteKey', function (): void {
    $turnstile = new Turnstile(siteKey: 'my-site-key', secretKey: 'my-secret-key');

    expect($turnstile->renderWidget())->toContain('my-site-key');
});

test('renderWidget includes the Cloudflare script src', function (): void {
    $turnstile = new Turnstile(siteKey: 'my-site-key', secretKey: 'my-secret-key');

    expect($turnstile->renderWidget())->toContain('https://challenges.cloudflare.com/turnstile/v0/api.js');
});

test('verificationUrl returns the correct URL', function (): void {
    $turnstile = new Turnstile(siteKey: 'my-site-key', secretKey: 'my-secret-key');

    expect($turnstile->verificationUrl())->toBe('https://challenges.cloudflare.com/turnstile/v0/siteverify');
});

test('verificationPayload returns array with secret and response keys', function (): void {
    $turnstile = new Turnstile(siteKey: 'my-site-key', secretKey: 'my-secret-key');
    $payload = $turnstile->verificationPayload('user-token');

    expect($payload)->toHaveKeys(['secret', 'response']);
    expect($payload['secret'])->toBe('my-secret-key');
    expect($payload['response'])->toBe('user-token');
});

test('siteKey returns the configured key', function (): void {
    $turnstile = new Turnstile(siteKey: 'my-site-key', secretKey: 'my-secret-key');

    expect($turnstile->siteKey())->toBe('my-site-key');
});
