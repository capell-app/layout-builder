<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\ContentSelect;
use Capell\LayoutBuilder\Models\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SettingsSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            ContentSelect::make('parent_id')
                ->label(__('capell-admin::form.parent'))
                ->lazy()
                ->modifySelectOptionsQueryUsing(function (Builder $query, ?Section $record): void {
                    if ($record instanceof Section) {
                        $query->where('sections.id', '!=', $record->id);
                    }
                })
                ->when(
                    $configurator->isCreating(),
                    fn (ContentSelect $component): ContentSelect => $component->withCreateForm(),
                    fn (ContentSelect $component): ContentSelect => $component->withEditForm(),
                ),

            SiteSelect::make('site_id')
                ->default(null)
                ->reactive(),
        ];
    }
}
