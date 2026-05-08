<?php

declare(strict_types=1);

namespace Capell\AccessGate\Database\Factories;

use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\ApprovalStrategy;
use Capell\AccessGate\Enums\IdentityMode;
use Capell\AccessGate\Enums\RegistrationPolicy;
use Capell\AccessGate\Enums\TokenPolicy;
use Capell\AccessGate\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    protected $model = Area::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $areaName = 'Access Gate ' . Str::random(12);

        return [
            'key' => str($areaName)->slug()->toString(),
            'site_id' => null,
            'name' => str($areaName)->title()->toString(),
            'status' => AccessAreaStatus::Active,
            'identity_mode' => IdentityMode::Hybrid,
            'approval_strategy' => ApprovalStrategy::Manual,
            'approval_limit' => null,
            'grant_duration_days' => null,
            'registration_policy' => RegistrationPolicy::SinglePerEmail,
            'token_policy' => TokenPolicy::SingleActiveBrowserToken,
            'public_allowlist' => [],
            'claim_url_hosts' => [],
            'gate_view' => null,
            'metadata' => [],
            'discount_label' => null,
            'discount_code' => null,
            'discount_expires_at' => null,
            'discount_metadata' => [],
        ];
    }
}
