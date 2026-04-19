<?php

declare(strict_types=1);

use Capell\Plugins\Capabilities\CapabilityRegistry;
use Capell\Plugins\Enums\Capability;
use Capell\Plugins\Enums\CapabilityWarningLevel;

test('registry returns a descriptor for every known capability', function (): void {
    foreach (Capability::cases() as $capability) {
        $parameter = $capability->acceptsParameter() ? 'storage' : null;
        // 'storage' is a valid value for writes_files; for http_outbound any string is valid (it's a host).
        if ($capability === Capability::HttpOutbound) {
            $parameter = 'example.com';
        }

        $descriptor = CapabilityRegistry::describe($capability, $parameter);
        expect($descriptor->capability)->toBe($capability);
        expect($descriptor->title)->not->toBeEmpty();
        expect($descriptor->summary)->not->toBeEmpty();
    }
});

test('reads_secrets is red', function (): void {
    expect(CapabilityRegistry::describe(Capability::ReadsSecrets)->warningLevel)
        ->toBe(CapabilityWarningLevel::Red);
});

test('admin_pages is green', function (): void {
    expect(CapabilityRegistry::describe(Capability::AdminPages)->warningLevel)
        ->toBe(CapabilityWarningLevel::Green);
});

test('parses parametric capability writes_files:storage', function (): void {
    $parsed = CapabilityRegistry::parse('writes_files:storage');
    expect($parsed->capability)->toBe(Capability::WritesFiles);
    expect($parsed->parameter)->toBe('storage');
    expect($parsed->warningLevel)->toBe(CapabilityWarningLevel::Yellow);
});

test('parses parametric capability writes_files:config as red', function (): void {
    $parsed = CapabilityRegistry::parse('writes_files:config');
    expect($parsed->warningLevel)->toBe(CapabilityWarningLevel::Red);
});

test('parses parametric capability http_outbound:api.acme.com', function (): void {
    $parsed = CapabilityRegistry::parse('http_outbound:api.acme.com');
    expect($parsed->capability)->toBe(Capability::HttpOutbound);
    expect($parsed->parameter)->toBe('api.acme.com');
    expect($parsed->warningLevel)->toBe(CapabilityWarningLevel::Yellow);
});

test('parses http_outbound:capell.app as green', function (): void {
    expect(CapabilityRegistry::parse('http_outbound:capell.app')->warningLevel)
        ->toBe(CapabilityWarningLevel::Green);
});

test('rejects unknown capability', function (): void {
    CapabilityRegistry::parse('not_a_real_capability');
})->throws(InvalidArgumentException::class);

test('http_outbound with capell.app subdomain is green', function (): void {
    expect(CapabilityRegistry::parse('http_outbound:api.capell.app')->warningLevel)
        ->toBe(CapabilityWarningLevel::Green);
    expect(CapabilityRegistry::parse('http_outbound:cdn.capell.app')->warningLevel)
        ->toBe(CapabilityWarningLevel::Green);
});

test('http_outbound with spoofed capell.app suffix is yellow (not green)', function (): void {
    expect(CapabilityRegistry::parse('http_outbound:evil-capell.app')->warningLevel)
        ->toBe(CapabilityWarningLevel::Yellow);
    expect(CapabilityRegistry::parse('http_outbound:fakecapell.app')->warningLevel)
        ->toBe(CapabilityWarningLevel::Yellow);
});

test('writes_files rejects unknown parameter', function (): void {
    CapabilityRegistry::parse('writes_files:brains');
})->throws(InvalidArgumentException::class, 'writes_files parameter must be one of');

test('describe() throws when parametric capability called without parameter', function (): void {
    CapabilityRegistry::describe(Capability::WritesFiles);
})->throws(InvalidArgumentException::class, 'requires a parameter');
