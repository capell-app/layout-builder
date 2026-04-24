<?php

declare(strict_types=1);

use Capell\Themes\Core\Forms\HoneypotField;

test('render contains the field name in the input', function (): void {
    $honeypot = new HoneypotField('hp_website');
    $html = $honeypot->render();

    expect($html)->toContain('name="hp_website"');
    expect($html)->toContain('id="hp_website"');
});

test('render includes tabindex and autocomplete attributes', function (): void {
    $honeypot = new HoneypotField('hp_website');
    $html = $honeypot->render();

    expect($html)->toContain('tabindex="-1"');
    expect($html)->toContain('autocomplete="off"');
});

test('validate returns true for empty submission', function (): void {
    $honeypot = new HoneypotField('hp_website');

    expect($honeypot->validate([]))->toBeTrue();
});

test('validate returns false when field is filled', function (): void {
    $honeypot = new HoneypotField('hp_website');

    expect($honeypot->validate(['hp_website' => 'http://spam.com']))->toBeFalse();
});

test('validate returns true when field is empty string', function (): void {
    $honeypot = new HoneypotField('hp_website');

    expect($honeypot->validate(['hp_website' => '']))->toBeTrue();
});
