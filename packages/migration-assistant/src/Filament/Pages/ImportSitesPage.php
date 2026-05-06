<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use RuntimeException;

/**
 * H3 placeholder. Recovery Center entry point for the SiteImport kind —
 * structurally equivalent to ImportPagesPage but wraps the full site
 * (Site / SiteDomain / Navigation) rather than just pages. The page is
 * registered in the Recovery Center navigation group now so the nav is
 * discoverable; every user-facing action throws
 * a clear placeholder exception until the migration-assistant package provides it.
 */
class ImportSitesPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::GlobeAlt;

    protected static ?string $slug = 'recovery-center/import-sites';

    protected string $view = 'capell-admin::components.pages.recovery-center-stub';

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::exchanger.import_sites');
    }

    /** @return array<NavigationItem> */
    public function getSubNavigation(): array
    {
        return [];
    }

    public function runImport(): never
    {
        throw new RuntimeException('Site imports are provided by the migration-assistant package.');
    }
}
