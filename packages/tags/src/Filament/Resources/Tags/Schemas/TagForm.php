<?php

declare(strict_types=1);

namespace Capell\Tags\Filament\Resources\Tags\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Components\Forms\NameInput;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Admin\Filament\Components\Forms\StatusToggle;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Support\Slug\SlugGenerator;
use Filament\FormBuilder\Components\Checkbox;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TagForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        return $configurator->components(self::getFormSchema($configurator))->columns();
    }

    protected static function getFormSchema(Schema $configurator): array
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
                        ->label(__('capell-layout-builder::form.slug'))
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
                                ->label(__('capell-layout-builder::form.featured'))
                                ->helperText(__('capell-admin::generic.featured_hint')),

                            StatusToggle::make('status'),
                        ]),
                ])
                ->contained(in_array($configurator->getOperation(), ['create', 'edit'], true)),
        ];
    }
}
