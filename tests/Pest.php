<?php

declare(strict_types=1);

use Capell\Address\Tests\AddressTestCase;
use Capell\Analytics\Tests\AnalyticsTestCase;
use Capell\Blog\Tests\BlogTestCase;
use Capell\Campaigns\Tests\CampaignsTestCase;
use Capell\DeveloperTools\Tests\DeveloperToolsTestCase;
use Capell\FilamentPeek\Tests\FilamentPeekTestCase;
use Capell\Forms\Tests\FormsTestCase;
use Capell\MediaCurator\Tests\MediaCuratorTestCase;
use Capell\Mosaic\Tests\MosaicTestCase;
use Capell\Navigation\Tests\NavigationTestCase;
use Capell\SeoTools\Tests\SeoToolsTestCase;
use Capell\SiteSearch\Tests\SiteSearchTestCase;
use Capell\Tags\Tests\TagsTestCase;
use Capell\Tests\Packages\PackagesTestCase;
use Capell\Themes\Admin\Tests\ThemesAdminTestCase;
use Capell\Themes\Core\Tests\ThemesCoreTestCase;
use Capell\Themes\Tests\ThemesTestCase;
use Capell\Toolbar\Tests\ToolbarTestCase;
use Capell\Workspaces\Tests\WorkspacesTestCase;

pest()->extend(AddressTestCase::class)->in('../packages/address/tests');
pest()->extend(AnalyticsTestCase::class)->in('../packages/analytics/tests');
pest()->extend(PackagesTestCase::class)->in('../packages/authentication-log/tests');
pest()->extend(BlogTestCase::class)->in('../packages/blog/tests');
pest()->extend(CampaignsTestCase::class)->in('../packages/campaigns/tests');
pest()->extend(DeveloperToolsTestCase::class)->in('../packages/developer-tools/tests');
pest()->extend(FilamentPeekTestCase::class)->in('../packages/filament-peek/tests');
pest()->extend(FormsTestCase::class)->in('../packages/forms/tests');
pest()->extend(MediaCuratorTestCase::class)->in('../packages/media-curator/tests');
pest()->extend(MosaicTestCase::class)->in('../packages/mosaic/tests');
pest()->extend(NavigationTestCase::class)->in('../packages/navigation/tests');
pest()->extend(PackagesTestCase::class)->in('Packages');
pest()->extend(SeoToolsTestCase::class)->in('../packages/seo-tools/tests');
pest()->extend(SiteSearchTestCase::class)->in('../packages/site-search/tests');
pest()->extend(TagsTestCase::class)->in('../packages/tags/tests');
pest()->extend(ThemesAdminTestCase::class)->in('../packages/themes-admin/tests');
pest()->extend(ThemesTestCase::class)->in('../packages/themes/tests');
pest()->extend(ThemesCoreTestCase::class)->in('../packages/themes-core/tests');
pest()->extend(ToolbarTestCase::class)->in('../packages/toolbar/tests');
pest()->extend(WorkspacesTestCase::class)->in('../packages/workspaces/tests');

uses()->in(
    '../packages/themes/agency/tests/Unit',
    '../packages/themes/corporate/tests/Unit',
    '../packages/themes/saas/tests/Unit',
);
