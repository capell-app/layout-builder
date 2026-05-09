<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\PublishingStudio\Filament\Settings\PublishingStudioSettingsSchema;
use Spatie\LaravelSettings\Settings;

final class PublishingStudioSettings extends Settings implements SettingsContract
{
    public bool $enable_user_resource_bridge = true;

    public static function group(): string
    {
        return 'publishing_studio';
    }

    public static function schema(): string
    {
        return PublishingStudioSettingsSchema::class;
    }
}
