<?php

declare(strict_types=1);

use Capell\Address\Tests\AddressTestCase;
use Capell\Blog\Tests\BlogTestCase;
use Capell\Mosaic\Tests\MosaicTestCase;
use Capell\Plugins\Tests\PluginsTestCase;
use Capell\Tags\Tests\TagsTestCase;
use Capell\Tests\Packages\PackagesTestCase;
use Capell\Themes\Admin\Tests\ThemesAdminTestCase;
use Capell\Themes\Core\Tests\ThemesCoreTestCase;
use Capell\Workspaces\Tests\WorkspacesTestCase;

pest()->extend(PackagesTestCase::class)->in('Packages');
pest()->extend(AddressTestCase::class)->in('../packages/address/tests');
pest()->extend(BlogTestCase::class)->in('../packages/blog/tests');
pest()->extend(MosaicTestCase::class)->in('../packages/mosaic/tests');
pest()->extend(PluginsTestCase::class)->in('../packages/plugins/tests');
pest()->extend(TagsTestCase::class)->in('../packages/tags/tests');
pest()->extend(ThemesCoreTestCase::class)->in('../packages/themes-core/tests');
pest()->extend(ThemesAdminTestCase::class)->in('../packages/themes-admin/tests');
pest()->extend(WorkspacesTestCase::class)->in('../packages/workspaces/tests');
