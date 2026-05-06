<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\BlockLibrary\Filament\Components\Forms\ContentSelect;
use Capell\BlockLibrary\Models\ContentBlock;
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
                ->modifySelectOptionsQueryUsing(function (Builder $query, ?ContentBlock $record): void {
                    if ($record instanceof ContentBlock) {
                        $query->where('block_library.id', '!=', $record->id);
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
