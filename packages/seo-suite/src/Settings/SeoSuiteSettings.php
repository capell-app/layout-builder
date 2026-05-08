<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\Core\Contracts\SettingsSchemaContract;
use Capell\SeoSuite\Filament\Settings\SeoSettingsSchema;
use Spatie\LaravelSettings\Settings;

class SeoSuiteSettings extends Settings implements SettingsContract, SettingsSchemaContract
{
    public bool $seo_audit_enabled = true;

    public bool $seo_check_meta_description = true;

    public bool $seo_check_meta_title = true;

    public bool $seo_check_duplicate_title = true;

    public static function group(): string
    {
        return 'seo_suite';
    }

    public static function schema(): string
    {
        return SeoSettingsSchema::class;
    }
}
