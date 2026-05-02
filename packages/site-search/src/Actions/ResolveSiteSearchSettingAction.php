<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Settings\SiteSearchSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ResolveSiteSearchSettingAction
{
    use AsAction;

    public function handle(string $settingKey, string $configKey, mixed $default): mixed
    {
        try {
            if (! app()->bound(SiteSearchSettings::class) && ! $this->hasPersistedSetting($settingKey)) {
                return config($configKey, $default);
            }

            if (class_exists(SiteSearchSettings::class)) {
                $settings = resolve(SiteSearchSettings::class);
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
            ->where('group', SiteSearchSettings::group())
            ->where('name', $settingKey)
            ->exists();
    }
}
