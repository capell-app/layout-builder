<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\PasswordPolicy\Filament\Settings\PasswordPolicySettingsSchema;
use Capell\PasswordPolicy\Settings\PasswordPolicySettings;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Override;
use UnitEnum;

class PasswordPolicySettingsPage extends SettingsPage
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string $settings = PasswordPolicySettings::class;

    protected static ?string $slug = 'password-policy/settings';

    protected static ?int $navigationSort = 93;

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('capell-password-policy::settings.title');
    }

    #[Override]
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('capell-admin::navigation.group_administration');
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-password-policy::settings.title');
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema->components(PasswordPolicySettingsSchema::make($schema));
    }
}
