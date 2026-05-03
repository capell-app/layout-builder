<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Resources\ContentBlocks\Pages;

use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\ContentBlocks\Enums\ResourceEnum;
use Capell\ContentBlocks\Filament\Actions\CreateContentAction;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\ContentBlockResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListContentBlocks extends ListRecords
{
    use ApplySearchRelationsTable;
    use HasSiteTableFilterTabs;

    protected string $siteRelation = 'content_blocks';

    /** @return class-string<ContentBlockResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::ContentBlock);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-content-blocks::generic.content_blocks_info');
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
