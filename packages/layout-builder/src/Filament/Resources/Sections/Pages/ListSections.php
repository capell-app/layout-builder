<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Sections\Pages;

use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Enums\ResourceEnum;
use Capell\LayoutBuilder\Filament\Actions\CreateContentAction;
use Capell\LayoutBuilder\Filament\Resources\Sections\SectionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSections extends ListRecords
{
    use ApplySearchRelationsTable;
    use HasSiteTableFilterTabs;

    protected string $siteRelation = 'sections';

    /** @return class-string<SectionResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Section);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-layout-builder::generic.sections_info');
    }

    protected function getActions(): array
    {
        return [
            CreateContentAction::make('create')
                ->redirectAfterCreate(),
        ];
    }

    protected function getSearchRelationColumns(): array
    {
        return [
            'translations' => [
                'content',
                'meta->label',
                'title',
            ],
        ];
    }
}
