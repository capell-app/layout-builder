<?php

declare(strict_types=1);

use Capell\AccessGate\Enums\GrantSubjectType;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Models\ClaimToken;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class);

it('respects the configured access gate database connection on models', function (): void {
    Config::set('access-gate.connection', 'access_gate_testing');

    expect((new Area)->getConnectionName())->toBe('access_gate_testing')
        ->and((new ClaimToken)->getConnectionName())->toBe('access_gate_testing')
        ->and((new BrowserToken)->getConnectionName())->toBe('access_gate_testing');
});

it('keeps token persistence limited to hashed token columns', function (): void {
    expect(Schema::hasColumn('access_gate_claim_tokens', 'token_hash'))->toBeTrue()
        ->and(Schema::hasColumn('access_gate_claim_tokens', 'token'))->toBeFalse()
        ->and(Schema::hasColumn('access_gate_claim_tokens', 'raw_token'))->toBeFalse()
        ->and(Schema::hasColumn('access_gate_browser_tokens', 'token_hash'))->toBeTrue()
        ->and(Schema::hasColumn('access_gate_browser_tokens', 'token'))->toBeFalse()
        ->and(Schema::hasColumn('access_gate_browser_tokens', 'raw_token'))->toBeFalse()
        ->and((new ClaimToken)->getFillable())->not->toContain('token', 'raw_token', 'plain_token')
        ->and((new BrowserToken)->getFillable())->not->toContain('token', 'raw_token', 'plain_token');
});

it('casts grant subject type to a backed enum', function (): void {
    $grant = new Grant([
        'subject_type' => GrantSubjectType::Email,
    ]);

    expect($grant->subject_type)->toBe(GrantSubjectType::Email);
});

it('creates browser token factories scoped to the same area as their grant', function (): void {
    $browserToken = BrowserToken::factory()->create();

    expect($browserToken->access_area_id)->toBe($browserToken->grant->access_area_id);
});
