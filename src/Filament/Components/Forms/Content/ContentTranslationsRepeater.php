<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\RepeaterTabs;
use Capell\Admin\Filament\Components\Forms\TranslationLanguageSelect;
use Capell\Admin\Filament\Components\Forms\TranslationsRepeater;
use Capell\Core\Support\CapellCoreHelper;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ContentTranslationsRepeater
{
    public static function make(
        Schema $schema,
        array $components = [],
        bool $hasTitle = true,
        bool $hasContent = true,
    ): RepeaterTabs {
        $operation = $schema->getOperation();

        return TranslationsRepeater::make('translations')
            ->when(
                $operation === 'replicate',
                fn (TranslationsRepeater $repeater): TranslationsRepeater => $repeater->withoutRelationship(),
            )
            ->schema([
                ...($hasTitle ? self::getTitleSchema() : []),
                ...($hasContent ? self::getContentSchema($schema) : []),
                ...$components,
            ]);
    }

    private static function getContentSchema(Schema $schema): array
    {
        $record = $schema->getRecord();

        if ($record && $record->relationLoaded('type')) {
            $type = $record->type;
        } else {
            $type = CapellCoreHelper::getType(
                typeId: $schema->getRawState()['type_id'] ?? null,
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
                        ->columnSpan(fn (Get $get): int => $get('language_id') ? 3 : 2)
                        ->requiredBasedOnType(),
                    TranslationLanguageSelect::make()
                        ->dehydratedWhenHidden()
                        ->hidden(fn (?int $state): bool => (bool) $state),
                ]),
        ];
    }
}
