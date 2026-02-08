<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Tags\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Blog\Enums\ResourceEnum;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Override;

class ListTags extends ListRecords
{
    use HasSiteTableFilterTabs;
    use Translatable;

    protected string $siteRelation = 'tags';

    /** @return class-string<Capell\Blog\Filament\Resources\Tags\TagResource> */
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Tag);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            LocaleSwitcher::make(),
        ];
    }
}
