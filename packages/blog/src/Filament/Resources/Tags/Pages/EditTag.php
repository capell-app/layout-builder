<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Tags\Pages;

use Capell\Admin\Contracts\PageCacheNotifiable;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\DeleteAction;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Resources\Tags\TagResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Override;

class EditTag extends EditRecord implements PageCacheNotifiable
{
    use HasPageCacheNotification;
    use Translatable;

    /** @return class-string<TagResource> */
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Tag);
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return new HtmlString(__('capell-layout::heading.edit_tag_record', [
            'name' => Str::limit($this->getRecordTitle(), 40),
        ]));
    }

    protected function getActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
            ActionGroup::make([
                CreateAction::make()
                    ->record($this->getRecord())
                    ->url(fn ($record) => static::getResource()::getUrl('create')),
            ]),
        ];
    }

    protected function afterSave(): void
    {
        $this->notifyPageCached($this->record);
    }
}
