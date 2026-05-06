<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\RepeaterTabs;
use Capell\Admin\Filament\Components\Forms\TranslationLanguageSelect;
use Capell\Admin\Filament\Components\Forms\TranslationsRepeater as BaseTranslationsRepeater;
use Capell\Core\Support\CapellCoreHelper;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TranslationsRepeater
{
    public static function make(
        Schema $configurator,
        array $components = [],
        bool $hasTitle = true,
        bool $hasContent = true,
    ): RepeaterTabs {
        $operation = $configurator->getOperation();

        return BaseTranslationsRepeater::make('translations')
            ->when(
                $operation === 'replicate',
                fn (TranslationsRepeater $repeater): TranslationsRepeater => $repeater->withoutRelationship(),
            )
            ->schema([
                ...($hasTitle ? self::getTitleSchema() : []),
                ...($hasContent ? self::getContentSchema($configurator) : []),
                ...$components,
            ]);
    }

    private static function getContentSchema(Schema $configurator): array
    {
        $record = $configurator->getRecord();

        if ($record && $record->relationLoaded('type')) {
            $type = $record->type;
        } else {
            $type = CapellCoreHelper::getType(
                typeId: $configurator->getRawState()['type_id'] ?? null,
            );
        }

        return [
            ContentEditor::make(structure: $type?->content_structure)
                ->requiredBasedOnType(),
        ];
    }

    private static function getTitleSchema(): array
    {
        return [
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
        ];
    }
}
