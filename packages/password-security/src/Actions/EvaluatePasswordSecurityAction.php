<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Actions;

use Capell\PasswordSecurity\Data\PasswordSecurityStatusData;
use Capell\PasswordSecurity\Support\PasswordSecuritySettingsResolver;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;

class EvaluatePasswordSecurityAction
{
    use AsObject;

    public function handle(Model $user): PasswordSecurityStatusData
    {
        $settings = resolve(PasswordSecuritySettingsResolver::class)->settings();

        if ($settings->forceChangeEnabled && Schema::hasColumn($user->getTable(), 'must_change_password')) {
            if ((bool) $user->getAttribute('must_change_password')) {
                return new PasswordSecurityStatusData(
                    mustChangePassword: true,
                    passwordExpired: false,
                    reason: 'forced',
                );
            }
        }

        if (! $settings->passwordExpiryEnabled || ! Schema::hasColumn($user->getTable(), 'password_changed_at')) {
            return new PasswordSecurityStatusData(false, false);
        }

        $changedAtValue = $user->getAttribute('password_changed_at');
        $changedAt = $changedAtValue instanceof CarbonInterface
            ? CarbonImmutable::instance($changedAtValue)
            : $this->parseChangedAt($changedAtValue);

        if (! $changedAt instanceof CarbonImmutable) {
            return new PasswordSecurityStatusData(
                mustChangePassword: false,
                passwordExpired: true,
                reason: 'missing_password_changed_at',
            );
        }

        $expiresAt = $changedAt->addDays(max(1, $settings->passwordExpiryDays));

        return new PasswordSecurityStatusData(
            mustChangePassword: false,
            passwordExpired: $expiresAt->isPast(),
            reason: $expiresAt->isPast() ? 'expired' : null,
        );
    }

    private function parseChangedAt(mixed $changedAtValue): ?CarbonImmutable
    {
        if (! is_string($changedAtValue) || $changedAtValue === '') {
            return null;
        }

        return CarbonImmutable::parse($changedAtValue);
    }
}
