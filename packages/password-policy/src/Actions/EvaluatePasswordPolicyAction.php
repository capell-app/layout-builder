<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Actions;

use Capell\PasswordPolicy\Data\PasswordPolicyStatusData;
use Capell\PasswordPolicy\Support\PasswordPolicySettingsResolver;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;

class EvaluatePasswordPolicyAction
{
    use AsObject;

    public function handle(Model $user): PasswordPolicyStatusData
    {
        $settings = resolve(PasswordPolicySettingsResolver::class)->settings();

        if ($settings->forceChangeEnabled && Schema::hasColumn($user->getTable(), 'must_change_password')) {
            if ((bool) $user->getAttribute('must_change_password')) {
                return new PasswordPolicyStatusData(
                    mustChangePassword: true,
                    passwordExpired: false,
                    reason: 'forced',
                );
            }
        }

        if (! $settings->passwordExpiryEnabled || ! Schema::hasColumn($user->getTable(), 'password_changed_at')) {
            return new PasswordPolicyStatusData(false, false);
        }

        $changedAtValue = $user->getAttribute('password_changed_at');
        $changedAt = $changedAtValue instanceof CarbonInterface
            ? CarbonImmutable::instance($changedAtValue)
            : $this->parseChangedAt($changedAtValue);

        if (! $changedAt instanceof CarbonImmutable) {
            return new PasswordPolicyStatusData(
                mustChangePassword: false,
                passwordExpired: true,
                reason: 'missing_password_changed_at',
            );
        }

        $expiresAt = $changedAt->addDays(max(1, $settings->passwordExpiryDays));

        return new PasswordPolicyStatusData(
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
