<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Components\Forms\Site;

use Capell\SeoTools\Filament\Components\Forms\SearchMetaDataSection;
use Capell\Core\Models\Translation;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;

class TranslationMetaSchema
{
    public static function make(array $components = []): array
    {
        return [
            Group::make()
                ->statePath('meta')
                ->schema([
                    SearchMetaDataSection::make()
                        ->schema([
                            TextInput::make('title_after_text')
                                ->label(__('capell-admin::form.meta_title_after_text'))
                                ->placeholder(
                                    fn (?Translation $record): string => __(
                                        'capell-admin::generic.meta_title_after_text',
                                        ['site' => $record?->title ?? config('app.name')],
                                    ),
                                ),
                            Textarea::make('description')
                                ->label(__('capell-admin::form.description'))
                                ->helperText(__('capell-admin::generic.site_default_meta_data'))
                                ->rows(2)
                                ->maxLength(250),
                        ]),
                    Textarea::make('footer_copy')
                        ->label(__('capell-admin::form.footer_copy'))
                        ->default('&copy; :year :name')
                        ->rows(3)
                        ->helperText(__('capell-admin::generic.footer_copy_info')),
                    ...$components,
                ]),
        ];
    }
}
