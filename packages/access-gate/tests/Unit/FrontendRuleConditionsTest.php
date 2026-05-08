<?php

declare(strict_types=1);

use Capell\AccessGate\Actions\CreateAccessGateBrowserTokenAction;
use Capell\AccessGate\Actions\CreateAccessGateGrantAction;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\GrantSubjectType;
use Capell\AccessGate\Enums\IdentityMode;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Frontend\Rules\AccessGateAreaStatusCondition;
use Capell\AccessGate\Frontend\Rules\AccessGateRegistrationStatusCondition;
use Capell\AccessGate\Frontend\Rules\HasActiveAccessGateGrantCondition;
use Capell\AccessGate\Frontend\Rules\MissingActiveAccessGateGrantCondition;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Registration;
use Capell\Frontend\Data\FrontendRuleContextData;
use Illuminate\Http\Request;

it('evaluates access gate active grant conditions from browser tokens', function (): void {
    $area = Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Hybrid,
    ]);
    $grant = resolve(CreateAccessGateGrantAction::class)->handle(
        area: $area,
        subjectType: GrantSubjectType::Email,
        email: 'mona@example.test',
    );
    $issuedToken = resolve(CreateAccessGateBrowserTokenAction::class)->handle($grant);
    $request = Request::create('/preview');
    $request->cookies->set(config('access-gate.cookies.browser_token.name'), $issuedToken->plainTextToken);

    expect(resolve(HasActiveAccessGateGrantCondition::class)->evaluate(
        ['area' => 'preview'],
        new FrontendRuleContextData($request),
    ))->toBeTrue();
});

it('does not treat inactive gate bypasses as active grants', function (): void {
    Area::factory()->create([
        'key' => 'preview',
        'status' => AccessAreaStatus::Paused,
        'identity_mode' => IdentityMode::Hybrid,
    ]);

    expect(resolve(HasActiveAccessGateGrantCondition::class)->evaluate(
        ['area' => 'preview'],
        new FrontendRuleContextData(Request::create('/preview')),
    ))->toBeFalse();
});

it('fails closed for missing active grant conditions with invalid areas', function (): void {
    Area::factory()->create([
        'key' => 'preview',
        'status' => AccessAreaStatus::Active,
        'identity_mode' => IdentityMode::Hybrid,
    ]);

    $condition = resolve(MissingActiveAccessGateGrantCondition::class);
    $context = new FrontendRuleContextData(Request::create('/preview'));

    expect($condition->evaluate(['area' => 'preview'], $context))->toBeTrue()
        ->and($condition->evaluate(['area' => 'missing-preview'], $context))->toBeFalse()
        ->and($condition->evaluate(['areas' => ['preview', 'missing-preview']], $context))->toBeFalse()
        ->and($condition->evaluate(['area' => ''], $context))->toBeFalse();
});

it('evaluates access gate area and registration status conditions', function (): void {
    $area = Area::factory()->create([
        'key' => 'preview',
        'status' => AccessAreaStatus::Paused,
    ]);
    Registration::factory()->for($area, 'area')->create([
        'email' => 'mona@example.test',
        'email_normalized' => 'mona@example.test',
        'status' => RegistrationStatus::Pending,
    ]);
    $context = new FrontendRuleContextData(Request::create('/preview'));

    expect(resolve(AccessGateAreaStatusCondition::class)->evaluate([
        'area' => 'preview',
        'status' => 'paused',
    ], $context))->toBeTrue()
        ->and(resolve(AccessGateRegistrationStatusCondition::class)->evaluate([
            'area' => 'preview',
            'email' => 'mona@example.test',
            'status' => 'pending',
        ], $context))->toBeTrue();
});
