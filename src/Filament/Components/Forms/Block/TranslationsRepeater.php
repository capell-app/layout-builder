<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Block;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\RepeaterTabs;
use Capell\Admin\Filament\Components\Forms\TranslationLanguageSelect;
use Capell\Admin\Filament\Components\Forms\TranslationsRepeater as BaseTranslationsRepeater;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Blueprint;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class TranslationsRepeater
{
    public static function make(Schema $configurator, array $components = []): RepeaterTabs
    {
        return BaseTranslationsRepeater::make('translations')
            ->when(
                $configurator->getOperation() === 'replicate',
                fn (BaseTranslationsRepeater $repeater): BaseTranslationsRepeater => $repeater->withoutRelationship(),
            )
            ->schema([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label(__('capell-admin::form.title'))
                            ->columnSpan(fn (Get $get): int => $get('language_id') !== null ? 3 : 2)
                            ->requiredBasedOnType(),
                        TranslationLanguageSelect::make()
                            ->dehydratedWhenHidden()
                            ->hidden(fn (?int $state): bool => (bool) $state),
                    ]),

                ContentEditor::make(structure: self::getContentStructure($configurator))
                    ->requiredBasedOnType(),

                ...$components,
            ]);
    }

    private static function getContentStructure(Schema $configurator): ?ContentStructure
    {
        $record = $configurator->getRecord();

        if (! $record instanceof Model || ! $record->relationLoaded('type')) {
            return null;
        }

        $type = $record->getRelationValue('type');

        return $type instanceof Blueprint ? $type->content_structure : null;
    }
}
