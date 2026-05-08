<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Models\Area;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsAction;

final class SetupDefaultAccessAreaAction
{
    use AsAction;

    public function handle(): Area
    {
        $areaConfig = config('access-gate.install.default_area', []);

        if (! is_array($areaConfig)) {
            $areaConfig = [];
        }

        $key = (string) Arr::get($areaConfig, 'key', 'capell-preview');

        return Area::query()->updateOrCreate([
            'key' => $key,
        ], [
            'name' => (string) Arr::get($areaConfig, 'name', 'Capell Preview'),
            'status' => (string) Arr::get($areaConfig, 'status', 'paused'),
            'identity_mode' => (string) Arr::get($areaConfig, 'identity_mode', 'hybrid'),
            'approval_strategy' => (string) Arr::get($areaConfig, 'approval_strategy', 'first_n_auto_approve'),
            'approval_limit' => $this->nullableInteger(Arr::get($areaConfig, 'approval_limit')),
            'grant_duration_days' => $this->nullableInteger(Arr::get($areaConfig, 'grant_duration_days')),
            'registration_policy' => (string) Arr::get($areaConfig, 'registration_policy', 'single_per_email'),
            'token_policy' => (string) Arr::get($areaConfig, 'token_policy', 'single_active_browser_token'),
        ]);
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
