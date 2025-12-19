<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\RepeaterTabs;
use Capell\Admin\Filament\Components\Forms\TranslationLanguageSelect;
use Capell\Admin\Filament\Components\Forms\TranslationsRepeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class WidgetTranslationsRepeater
{
    public static function make(Schema $schema, array $components = []): RepeaterTabs
    {
        return TranslationsRepeater::make('translations')
            ->when(
                $schema->getOperation() === 'replicate',
                fn (TranslationsRepeater $repeater): TranslationsRepeater => $repeater->withoutRelationship(),
            )
            ->schema([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label(__('capell-admin::form.title'))
                            ->columnSpan(fn (Get $get): int => $get('language_id') ? 3 : 2)
                            ->requiredBasedOnType(),
                        TranslationLanguageSelect::make()
                            ->dehydratedWhenHidden()
                            ->hidden(fn (?int $state): bool => (bool) $state),
                    ]),

                ContentEditor::make(structure: $schema->getRecord()?->type->content_structure)
                    ->requiredBasedOnType(),

                ...$components,
            ]);
    }
}
