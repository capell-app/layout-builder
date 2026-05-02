<?php

declare(strict_types=1);

use Capell\AuthenticationLog\Filament\Resources\AuthenticationLogs\Tables\AuthenticationLogsTable;
use Capell\AuthenticationLog\Models\AuthenticationLog;
use Capell\Tests\Fixtures\Models\User;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;

function authenticationLogAuthenticatableColumn(): TextColumn
{
    $reflectionMethod = new ReflectionMethod(AuthenticationLogsTable::class, 'getTableColumns');

    /** @var array<int, mixed> $columns */
    $columns = $reflectionMethod->invoke(null);

    return $columns[1];
}

function formatAuthenticationLogAuthenticatableColumn(TextColumn $column, AuthenticationLog $authenticationLog): mixed
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

    $authenticationLog = AuthenticationLog::factory()->create([
        'authenticatable_type' => $user->getMorphClass(),
        'authenticatable_id' => $user->getKey(),
    ]);
    $authenticationLog->setRelation('authenticatable', $user);

    $column = authenticationLogAuthenticatableColumn()->record($authenticationLog);
    $formattedState = formatAuthenticationLogAuthenticatableColumn($column, $authenticationLog);

    expect($formattedState)
        ->toBe('Ben Johnson')
        ->toBeString()
        ->not->toBeInstanceOf(HtmlString::class);
});

it('configures the vendor authentication log table to display user names', function (): void {
    expect(config('filament-authentication-log.authenticatable.field-to-display'))->toBe('name');
});

it('renders a placeholder for orphaned authentication logs', function (): void {
    $authenticationLog = new AuthenticationLog;
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

    expect(fn (): mixed => formatAuthenticationLogAuthenticatableColumn($column, $authenticationLog))
        ->not->toThrow(Throwable::class)
        ->and(formatAuthenticationLogAuthenticatableColumn($column, $authenticationLog))
        ->toBe(__('capell-admin::generic.missing'));
});
