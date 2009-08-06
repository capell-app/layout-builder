<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\ListPages;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

it('applies the layout filter when loading its generated list URL', function (): void {
    $this->actingAsAdmin();
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)
        ->has(SiteDomain::factory()->default()->language($language), 'siteDomains')->create();
    $layout = Layout::factory()->site($site)->create();
    $pages = Page::factory()->site($site)->layout($layout)
        ->has(Translation::factory()->state(['language_id' => $language->id]), 'translations')
        ->count(2)->create();
    $otherPage = Page::factory()->site($site)
        ->has(Translation::factory()->state(['language_id' => $language->id]), 'translations')->create();

    $builder = new LayoutBuilder;
    $builder->layout = $layout;
    parse_str(str($builder->getPagesUsingLayoutUrl())->after('?')->toString(), $query);

    Livewire::withQueryParams($query)->test(ListPages::class)
        ->assertSuccessful()
        ->assertCountTableRecords(2)
        ->assertCanSeeTableRecords($pages)
        ->assertCanNotSeeTableRecords([$otherPage])
        ->assertSet('tableFilters.layout_id.value', (string) $layout->id);
});
