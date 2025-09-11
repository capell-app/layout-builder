<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Filament\Actions\Page\CreateContentModalAction;
use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListContents extends ListRecords
{
    use ApplySearchRelationsTable;
    use HasSiteTableFilterTabs;

    protected string $siteRelation = 'contents';

    /** @return class-string<ContentResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(LayoutResourceEnum::Content);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-layout::generic.contents_subheading');
    }

    protected function getActions(): array
    {
        return [
            CreateContentModalAction::make()
                ->redirectAfterCreate(),
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
