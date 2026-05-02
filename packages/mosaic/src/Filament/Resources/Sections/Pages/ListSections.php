<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Sections\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Mosaic\Enums\ResourceEnum;
use Capell\Mosaic\Filament\Actions\CreateContentAction;
use Capell\Mosaic\Filament\Resources\Sections\SectionResource;
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
        return CapellAdmin::getResource(ResourceEnum::Section);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-mosaic::generic.sections_info');
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
