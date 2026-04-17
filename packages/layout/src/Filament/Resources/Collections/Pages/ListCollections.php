<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Collections\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Layout\Enums\ResourceEnum;
use Capell\Layout\Filament\Actions\CreateContentAction;
use Capell\Layout\Filament\Resources\Collections\CollectionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCollections extends ListRecords
{
    use ApplySearchRelationsTable;
    use HasSiteTableFilterTabs;

    protected string $siteRelation = 'contents';

    /** @return class-string<CollectionResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Content);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-layout::generic.contents_info');
    }

    protected function getActions(): array
    {
        return [
            CreateContentAction::make()
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
