<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\Site\SiteSelect;
use Capell\Layout\Models\Content;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ContentSettingsSchema
{
    public static function make(Schema $schema): array
    {
        return [
            ContentSelect::make('parent_id')
                ->label(__('capell-admin::form.parent'))
                ->withEditForm()
                ->lazy()
                ->modifySelectOptionsQueryUsing(function (Builder $query, ?Content $record): void {
                    if ($record instanceof Content) {
                        $query->where('contents.id', '!=', $record->id);
                    }
                }),

            SiteSelect::make('site_id')
                ->default(null)
                ->reactive(),

            ContentTagsInput::make('tags'),
        ];
    }
}
