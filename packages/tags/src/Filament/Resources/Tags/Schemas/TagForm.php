<?php

declare(strict_types=1);

namespace Capell\Tags\Filament\Resources\Tags\Schemas;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Admin\Filament\Components\Forms\StatusToggle;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\SlugGenerator;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TagForm implements FormConfigurator
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::getFormSchema($schema))->columns();
    }

    protected static function getFormSchema(Schema $schema): array
    {
        return [
            Section::make()
                ->columns()
                ->columnSpanFull()
                ->schema([
                    NameInput::make('name')
                        ->afterStateUpdatedJs(function (string $operation): string {
                            if (! in_array($operation, ['create', 'createOption', 'replicate'], true)) {
                                return '';
                            }

                            return SlugGenerator::slugifyState("\$state ?? ''", 'slug');
                        }),

                    TextInput::make('slug')
                        ->label(__('capell-mosaic::form.slug'))
                        ->alphaDash()
                        ->required()
                        ->maxLength(128)
                        ->required(),

                    TextInput::make('type')
                        ->label(__('capell-admin::form.type'))
                        ->default('page'),

                    SiteSelect::make('site_id'),

                    Grid::make()
                        ->columnSpanFull()
                        ->schema([
                            Checkbox::make('featured')
                                ->label(__('capell-mosaic::form.featured'))
                                ->helperText(__('capell-admin::generic.featured_hint')),

                            StatusToggle::make('status'),
                        ]),
                ])
                ->contained(in_array($schema->getOperation(), ['create', 'edit'], true)),
        ];
    }
}
