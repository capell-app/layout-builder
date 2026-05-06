<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\Resources\BlockLibrary\Pages;

use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\BlockLibrary\Enums\ResourceEnum;
use Capell\BlockLibrary\Filament\Actions\CreateContentAction;
use Capell\BlockLibrary\Filament\Resources\BlockLibrary\ContentBlockResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListBlockLibrary extends ListRecords
{
    use ApplySearchRelationsTable;
    use HasSiteTableFilterTabs;

    protected string $siteRelation = 'block_library';

    /** @return class-string<ContentBlockResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::ContentBlock);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-block-library::generic.block_library_info');
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
