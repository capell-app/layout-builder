<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Schemas\Widget;

use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetAdminSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Schemas\AbstractWidgetSchema;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ArticleWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'create', 'createOption', 'replicate' => [
                self::getArticleSettingsSchema(),
            ],
            'editOption' => [
                Section::make(__('capell-admin::generic.settings'))
                    ->columns()
                    ->compact()
                    ->collapsed()
                    ->schema([
                        ...WidgetSettingsSchema::make($schema),
                        self::getArticleSettingsSchema(),
                    ]),
            ],
            default => [
                Tabs::make('tabs')
                    ->visibleOn(['edit', 'editOption'])
                    ->columnSpanFull()
                    ->tabs([
                        WidgetDisplayTab::make([
                            ...WidgetSettingsSchema::make($schema),
                            self::getArticleSettingsSchema(),
                        ]),
                        Tab::make(__('capell-admin::generic.admin'))
                            ->statePath('admin')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->columns(['md' => 2])
                            ->schema([
                                WidgetAdminSchema::make(),
                            ]),
                    ]),
            ],
        };
    }

    private static function getArticleSettingsSchema(): Fieldset
    {
        return Fieldset::make(__('capell-blog::generic.article'))
            ->statePath('meta')
            ->columns(['default' => 1, 'md' => 2, 'lg' => 4])
            ->columnSpanFull()
            ->schema([
                Checkbox::make('with_date')
                    ->label(__('capell-admin::form.published_date')),
                Checkbox::make('with_next_prev')
                    ->label(__('capell-admin::form.next_prev')),
                Checkbox::make('with_author')
                    ->label(__('capell-admin::form.author')),
                Checkbox::make('with_tags')
                    ->label(__('capell-admin::form.tags')),
            ]);
    }
}
