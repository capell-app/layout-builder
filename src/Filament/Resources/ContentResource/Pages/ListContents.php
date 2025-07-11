<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\ContentResource\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Filament\Actions\Page\CreateContentAction;
use Capell\Layout\Filament\Resources\ContentResource;
use Filament\Resources\Pages\ListRecords;

class ListContents extends ListRecords
{
    use ApplySearchRelationsTable;
    use HasSiteTableFilterTabs;

    protected string $siteRelation = 'contents';

    /** @return class-string<ContentResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(LayoutResourceEnum::Content->name);
    }

    protected function getActions(): array
    {
        return [
            CreateContentAction::make(),
        ];
    }

    protected function getSearchRelationColumns(): array
    {
        return [
            'translations' => [
                'contents' => 'json_data',
                'meta->label',
                'title',
            ],
        ];
    }
}
