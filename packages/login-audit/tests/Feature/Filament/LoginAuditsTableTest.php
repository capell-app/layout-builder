<?php

declare(strict_types=1);

use Capell\LoginAudit\Filament\Resources\LoginAudits\Tables\LoginAuditsTable;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\Tests\Fixtures\Models\User;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;

function authenticationLogAuthenticatableColumn(): TextColumn
{
    $reflectionMethod = new ReflectionMethod(LoginAuditsTable::class, 'getTableColumns');

    /** @var array<int, mixed> $columns */
    $columns = $reflectionMethod->invoke(null);

    return $columns[1];
}

function formatLoginAuditAuthenticatableColumn(TextColumn $column, LoginAudit $authenticationLog): mixed
{
    foreach (['getStateUsing', 'formatStateUsing'] as $propertyName) {
        $reflectionProperty = new ReflectionProperty($column, $propertyName);

        $callback = $reflectionProperty->getValue($column);

        if ($callback instanceof Closure) {
            $reflectionFunction = new ReflectionFunction($callback);
            $firstParameter = $reflectionFunction->getParameters()[0] ?? null;

            if ($firstParameter?->getName() === 'record') {
                return $callback($authenticationLog);
            }

            return $callback(null, $authenticationLog);
        }
    }

    return null;
}

it('renders authenticatable names as safe text instead of raw html', function (): void {
    $user = User::factory()->create([
        'name' => 'Ben Johnson',
    ]);

    $authenticationLog = LoginAudit::factory()->create([
        'authenticatable_type' => $user->getMorphClass(),
        'authenticatable_id' => $user->getKey(),
    ]);
    $authenticationLog->setRelation('authenticatable', $user);

    $column = authenticationLogAuthenticatableColumn()->record($authenticationLog);
    $formattedState = formatLoginAuditAuthenticatableColumn($column, $authenticationLog);

    expect($formattedState)
        ->toBe('Ben Johnson')
        ->toBeString()
        ->not->toBeInstanceOf(HtmlString::class);
});

it('configures the vendor authentication log table to display user names', function (): void {
    expect(config('filament-login-audit.authenticatable.field-to-display'))->toBe('name');
});

it('renders a placeholder for orphaned authentication logs', function (): void {
    $authenticationLog = new LoginAudit;
    $authenticationLog->forceFill([
        'authenticatable_type' => (new User)->getMorphClass(),
        'authenticatable_id' => 999_999,
        'ip_address' => '203.0.113.10',
        'user_agent' => 'Capell Test Browser',
        'login_at' => now(),
        'login_successful' => true,
    ]);
    $authenticationLog->save();
    $authenticationLog->setRelation('authenticatable', null);

    $column = authenticationLogAuthenticatableColumn()->record($authenticationLog);

    expect(fn (): mixed => formatLoginAuditAuthenticatableColumn($column, $authenticationLog))
        ->not->toThrow(Throwable::class)
        ->and(formatLoginAuditAuthenticatableColumn($column, $authenticationLog))
        ->toBe(__('capell-admin::generic.missing'));
});
