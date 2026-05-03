<?php

declare(strict_types=1);

namespace Capell\Tags\Filament\Resources\Tags\Pages;

use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Tags\Enums\ResourceEnum;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Override;

class ListTags extends ListRecords
{
    use HasSiteTableFilterTabs;
    use Translatable;

    protected string $siteRelation = 'tags';

    /** @return class-string<TagResource> */
    #[Override]
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Tag);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-tags::generic.tags_info');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->redirectAfterCreate(),
            LocaleSwitcher::make(),
        ];
    }
}
