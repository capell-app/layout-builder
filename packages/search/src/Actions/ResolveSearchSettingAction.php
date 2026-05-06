<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Capell\Search\Settings\SearchSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ResolveSearchSettingAction
{
    use AsAction;

    public function handle(string $settingKey, string $configKey, mixed $default): mixed
    {
        try {
            if (! app()->bound(SearchSettings::class) && ! $this->hasPersistedSetting($settingKey)) {
                return config($configKey, $default);
            }

            if (class_exists(SearchSettings::class)) {
                $settings = resolve(SearchSettings::class);
                $settingsValue = data_get($settings, $settingKey);

                if ($settingsValue !== null) {
                    return $settingsValue;
                }
            }
        } catch (Throwable) {
            //
        }

        return config($configKey, $default);
    }

    private function hasPersistedSetting(string $settingKey): bool
    {
        if (! SchemaFacade::hasTable('settings')) {
            return false;
        }

        return DB::table('settings')
            ->where('group', SearchSettings::group())
            ->where('name', $settingKey)
            ->exists();
    }
}
