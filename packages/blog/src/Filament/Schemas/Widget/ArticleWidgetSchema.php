<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Schemas\Widget;

use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetAdminSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Schemas\Widget\AbstractWidgetSchema;
use Filament\Forms;

class ArticleWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        $operation = $form->getOperation();

        return match ($operation) {
            'create', 'createOption', 'replicate' => [
                self::getArticleSettingsSchema(),
            ],
            'editOption' => [
                Forms\Components\Section::make(__('capell-admin::generic.settings'))
                    ->columns()
                    ->compact()
                    ->collapsed()
                    ->schema([
                        ...WidgetSettingsSchema::make($form),
                        self::getArticleSettingsSchema(),
                    ]),
            ],
            default => [
                Forms\Components\Tabs::make('tabs')
                    ->visibleOn(['edit', 'editOption'])
                    ->columnSpanFull()
                    ->tabs([
                        WidgetSettingsTab::make([
                            ...WidgetSettingsSchema::make($form),
                            self::getArticleSettingsSchema(),
                        ]),
                        Forms\Components\Tabs\Tab::make(__('capell-admin::generic.admin'))
                            ->statePath('admin')
                            ->icon('heroicon-o-cog')
                            ->columns(['md' => 2])
                            ->schema([
                                WidgetAdminSchema::make(),
                            ]),
                    ]),
            ],
        };
    }

    private static function getArticleSettingsSchema(): Forms\Components\Fieldset
    {
        return Forms\Components\Fieldset::make(__('capell-blog::generic.article'))
            ->statePath('meta')
            ->columns(['default' => 1, 'md' => 2, 'lg' => 4])
            ->columnSpanFull()
            ->schema([
                Forms\Components\Checkbox::make('with_published')
                    ->label(__('capell-admin::form.published_date')),
                Forms\Components\Checkbox::make('with_next_prev')
                    ->label(__('capell-admin::form.next_prev')),
                Forms\Components\Checkbox::make('with_author')
                    ->label(__('capell-admin::form.author')),
                Forms\Components\Checkbox::make('with_tags')
                    ->label(__('capell-admin::form.tags')),
            ]);
    }
}
