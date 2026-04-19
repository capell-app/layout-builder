<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types;

use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Repeater;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;
use Override;

class FormSectionWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    protected function displayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }

    #[Override]
    protected function detailsTab(): Tab
    {
        return Tab::make('form_details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Fieldset::make(__('capell-mosaic::form.form_settings'))
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextInput::make('title')
                            ->label(__('capell-mosaic::form.title'))
                            ->placeholder('Contact Us')
                            ->required(),
                        Textarea::make('description')
                            ->label(__('capell-mosaic::form.description'))
                            ->placeholder('Get in touch with our team')
                            ->rows(2),
                        TextInput::make('submit_text')
                            ->label(__('capell-mosaic::form.submit_button_text'))
                            ->placeholder('Send Message')
                            ->required(),
                        TextInput::make('submit_action')
                            ->label(__('capell-mosaic::form.submit_action'))
                            ->placeholder('/contact')
                            ->url()
                            ->required(),
                    ]),
                Fieldset::make(__('capell-mosaic::form.form_fields'))
                    ->columns(['default' => 1])
                    ->schema([
                        Repeater::make('fields')
                            ->label('')
                            ->addActionLabel(__('capell-mosaic::form.add_field'))
                            ->columns(['default' => 1, 'lg' => 2])
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('capell-mosaic::form.field_name'))
                                    ->placeholder('email')
                                    ->required(),
                                TextInput::make('label')
                                    ->label(__('capell-mosaic::form.field_label'))
                                    ->placeholder('Email Address')
                                    ->required(),
                                Select::make('type')
                                    ->label(__('capell-mosaic::form.field_type'))
                                    ->options([
                                        'text' => 'Text',
                                        'email' => 'Email',
                                        'number' => 'Number',
                                        'textarea' => 'Textarea',
                                        'select' => 'Select',
                                        'checkbox' => 'Checkbox',
                                    ])
                                    ->default('text')
                                    ->required(),
                                TextInput::make('placeholder')
                                    ->label(__('capell-mosaic::form.placeholder'))
                                    ->placeholder('you@example.com'),
                                Textarea::make('help_text')
                                    ->label(__('capell-mosaic::form.help_text'))
                                    ->placeholder("We'll never share your email")
                                    ->rows(2),
                                Toggle::make('required')
                                    ->label(__('capell-mosaic::form.required'))
                                    ->default(false),
                                TextInput::make('checkbox_label')
                                    ->label(__('capell-mosaic::form.checkbox_label'))
                                    ->placeholder('I agree')
                                    ->visible(fn (callable $get): bool => $get('type') === 'checkbox'),
                            ]),
                    ]),
            ]);
    }
}
