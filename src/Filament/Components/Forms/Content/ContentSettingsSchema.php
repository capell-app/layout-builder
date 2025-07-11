<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\Site\SiteSelect;
use Capell\Layout\Models\Content;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

class ContentSettingsSchema
{
    public static function make(Forms\Form $form): array
    {
        return [
            ...match ($form->getOperation()) {
                'create', 'edit', 'editOption' => [
                    ContentSelect::make('parent_uuid')
                        ->label(__('capell-admin::form.parent'))
                        ->withUuid()
                        ->withEditForm()
                        ->lazy()
                        ->hiddenOn(['replicate', 'createOption'])
                        ->modifySelectOptionsQueryUsing(function (Builder $query, ?Content $record): void {
                            if ($record instanceof Content) {
                                $query->where('contents.uuid', '!=', $record->uuid);
                            }
                        }),

                    SiteSelect::make('site_id')
                        ->default(null)
                        ->reactive(),
                ],
                default => [],
            },

            ...match ($form->getOperation()) {
                'edit', 'editOption' => [ContentTagsInput::make('tags')],
                default => [],
            },
        ];
    }
}
