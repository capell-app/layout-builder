<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Admin\Filament\Pages\AbstractPackageSettingsPage;

final class SeoSuiteSettingsPage extends AbstractPackageSettingsPage
{
    use HasPageShield;

    protected static string $settingsGroup = 'seo_suite';

    protected static ?string $slug = 'extensions/seo-suite/settings';
}
