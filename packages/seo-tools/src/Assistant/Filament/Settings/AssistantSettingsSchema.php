<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AssistantSettingsSchema implements HasSchema
{
    public static function make(Schema $schema): array
    {
        return [
            Grid::make()
                ->statePath('prompts')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('model')
                        ->label(__('capell-assistant::form.model'))
                        ->helperText(__('capell-assistant::generic.model_info'))
                        ->placeholder(config('capell-assistant.openai.default_model')),
                    TextInput::make('rate_limiting_requests_per_minute')
                        ->label(__('capell-assistant::form.rate_limiting'))
                        ->helperText(__('capell-assistant::generic.rate_limiting_info'))
                        ->placeholder((string) config('capell-assistant.rate_limiting.requests_per_minute')),
                    Fieldset::make()
                        ->label(__('capell-assistant::form.title_generation'))
                        ->columnSpanFull()
                        ->schema([
                            Checkbox::make('title_generation')
                                ->label(__('capell-admin::form.enabled'))
                                ->reactive(),
                            Grid::make()
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => $get('title_generation') === true)
                                ->schema([
                                    Textarea::make('title_generation_system')
                                        ->label(__('capell-assistant::form.system'))
                                        ->rows(4),
                                    Textarea::make('title_generation_user_template')
                                        ->label(__('capell-assistant::form.user_template'))
                                        ->rows(4),
                                ]),
                        ]),
                    Fieldset::make()
                        ->label(__('capell-assistant::form.meta_description'))
                        ->columnSpanFull()
                        ->schema([
                            Checkbox::make('meta_description')
                                ->label(__('capell-admin::form.enabled'))
                                ->reactive(),
                            Grid::make()
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => $get('meta_description') === true)
                                ->schema([
                                    Textarea::make('meta_description_system')
                                        ->label(__('capell-assistant::form.system'))
                                        ->rows(4),
                                    Textarea::make('meta_description_user_template')
                                        ->label(__('capell-assistant::form.user_template'))
                                        ->rows(4),
                                ]),
                        ]),
                    Fieldset::make()
                        ->label(__('capell-assistant::form.content_generation'))
                        ->columnSpanFull()
                        ->schema([
                            Checkbox::make('content_generation')
                                ->label(__('capell-admin::form.enabled'))
                                ->reactive(),
                            Grid::make()
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => $get('content_generation') === true)
                                ->schema([
                                    Textarea::make('content_generation_system')
                                        ->label(__('capell-assistant::form.system'))
                                        ->rows(4),
                                    Textarea::make('content_generation_user_template')
                                        ->label(__('capell-assistant::form.user_template'))
                                        ->rows(4),
                                ]),
                        ]),
                ]),
        ];
    }
}
