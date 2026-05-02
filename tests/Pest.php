<?php

declare(strict_types=1);

use Capell\Address\Tests\AddressTestCase;
use Capell\Analytics\Tests\AnalyticsTestCase;
use Capell\Assistant\Tests\AssistantTestCase;
use Capell\Backup\Tests\BackupTestCase;
use Capell\Blog\Tests\BlogTestCase;
use Capell\Campaigns\Tests\CampaignsTestCase;
use Capell\DeveloperTools\Tests\DeveloperToolsTestCase;
use Capell\FilamentPeek\Tests\FilamentPeekTestCase;
use Capell\Forms\Tests\FormsTestCase;
use Capell\HtmlMinify\Tests\HtmlMinifyTestCase;
use Capell\MediaCurator\Tests\MediaCuratorTestCase;
use Capell\Mosaic\Tests\MosaicTestCase;
use Capell\Navigation\Tests\NavigationTestCase;
use Capell\Redirects\Tests\RedirectsTestCase;
use Capell\SeoTools\Tests\SeoToolsTestCase;
use Capell\SiteSearch\Tests\SiteSearchTestCase;
use Capell\Tags\Tests\TagsTestCase;
use Capell\Tests\Packages\PackagesTestCase;
use Capell\ThemeStudio\Admin\Tests\ThemeStudioAdminTestCase;
use Capell\ThemeStudio\Core\Tests\ThemeStudioCoreTestCase;
use Capell\Toolbar\Tests\ToolbarTestCase;
use Capell\Workspaces\Tests\WorkspacesTestCase;

pest()->extend(AddressTestCase::class)->in('../packages/foundation/address/tests');
pest()->extend(AnalyticsTestCase::class)->in('../packages/growth/analytics/tests');
pest()->extend(AssistantTestCase::class)->in('../packages/commercial/assistant/tests');
pest()->extend(BackupTestCase::class)->group('backup')->in('../packages/operations/backup/tests');
pest()->extend(PackagesTestCase::class)->in('../packages/operations/authentication-log/tests');
pest()->extend(BlogTestCase::class)->in('../packages/foundation/blog/tests');
pest()->extend(CampaignsTestCase::class)->in('../packages/growth/campaigns/tests');
pest()->extend(DeveloperToolsTestCase::class)->in('../packages/operations/developer-tools/tests');
pest()->extend(FilamentPeekTestCase::class)->in('../packages/publishing-pro/filament-peek/tests');
pest()->extend(FormsTestCase::class)->in('../packages/forms/forms/tests');
pest()->extend(HtmlMinifyTestCase::class)->in('../packages/foundation/html-minify/tests');
pest()->extend(MediaCuratorTestCase::class)->in('../packages/foundation/media-curator/tests');
pest()->extend(MosaicTestCase::class)->in('../packages/foundation/mosaic/tests');
pest()->extend(NavigationTestCase::class)->in('../packages/foundation/navigation/tests');
pest()->extend(PackagesTestCase::class)->in('Packages');
pest()->extend(RedirectsTestCase::class)->group('redirects')->in('../packages/foundation/redirects/tests');
pest()->extend(SeoToolsTestCase::class)->in('../packages/search-seo/seo-tools/tests');
pest()->extend(SiteSearchTestCase::class)->in('../packages/search-seo/site-search/tests');
pest()->extend(TagsTestCase::class)->in('../packages/foundation/tags/tests');
pest()->extend(ThemeStudioAdminTestCase::class)->in('../packages/theme-studio/admin/tests');
pest()->extend(ThemeStudioCoreTestCase::class)->in('../packages/theme-studio/core/tests');
pest()->extend(ToolbarTestCase::class)->in('../packages/foundation/toolbar/tests');
pest()->extend(WorkspacesTestCase::class)->in('../packages/publishing-pro/workspaces/tests');

uses()->in(
    '../packages/theme-studio/themes/agency/tests/Unit',
    '../packages/theme-studio/themes/corporate/tests/Unit',
    '../packages/theme-studio/themes/saas/tests/Unit',
);
