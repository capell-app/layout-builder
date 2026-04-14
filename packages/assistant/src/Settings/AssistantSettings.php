<?php

declare(strict_types=1);

namespace Capell\Assistant\Settings;

use Capell\Assistant\Filament\Settings\AssistantSettingsSchema;
use Capell\Core\Contracts\SettingsContract;
use Spatie\LaravelSettings\Settings;

class AssistantSettings extends Settings implements SettingsContract
{
    public array $prompts;

    public static function group(): string
    {
        return 'assistant';
    }

    public static function schema(): string
    {
        return AssistantSettingsSchema::class;
    }
}
