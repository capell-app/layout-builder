<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Layout\Filament\Components\Forms\ContentSelect;
use Capell\Layout\Models\Content;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SettingsSchema
{
    public static function make(Schema $schema): array
    {
        return [
            ContentSelect::make('parent_id')
                ->label(__('capell-admin::form.parent'))
                ->lazy()
                ->modifySelectOptionsQueryUsing(function (Builder $query, ?Content $record): void {
                    if ($record instanceof Content) {
                        $query->where('contents.id', '!=', $record->id);
                    }
                })
                ->when(
                    $schema->isCreating(),
                    fn (ContentSelect $component): ContentSelect => $component->withCreateForm(),
                    fn (ContentSelect $component): ContentSelect => $component->withEditForm(),
                ),

            SiteSelect::make('site_id')
                ->default(null)
                ->reactive(),
        ];
    }
}
