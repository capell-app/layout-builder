<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\RepeaterTabs;
use Capell\Admin\Filament\Components\Forms\TranslationLanguageSelect;
use Capell\Admin\Filament\Components\Forms\TranslationsRepeater;
use Capell\Admin\Filament\Components\Forms\TranslationTitle;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\TypeEnum;
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
        bool $titleRequired = true,
    ): RepeaterTabs {
        $operation = $schema->getOperation();

        return TranslationsRepeater::make('translations')
            ->when(
                $operation === 'replicate',
                fn (TranslationsRepeater $repeater): TranslationsRepeater => $repeater->withoutRelationship(),
            )
            ->schema([
                ...($hasTitle ? self::getTitleSchema($titleRequired) : []),
                ...($hasContent ? self::getContentSchema($schema) : []),
                ...$components,
            ]);
    }

    private static function getContentSchema(Schema $schema): array
    {
        $type = $schema->getRecord()?->type ?? null;

        if (! $type && $typeId = $schema->getRawState()['type_id'] ?? null) {
            $type = CapellCore::getModel(ModelEnum::Type)::query()
                ->where('type', TypeEnum::Content)
                ->whereKey($typeId)
                ->first();
        }

        return [
            ContentEditor::make(editor: $type?->admin['content_editor'] ?? null),
        ];
    }

    private static function getTitleSchema(bool $titleRequired): array
    {
        return [
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    ...TranslationTitle::make(
                        modifyTitle: fn (TextInput $component): TextInput => $component->required($titleRequired)
                            ->columnSpan(fn (Get $get): int => $get('language_id') ? 3 : 2),
                    ),

                    TranslationLanguageSelect::make()
                        ->dehydratedWhenHidden()
                        ->hidden(fn (?int $state): bool => (bool) $state),
                ]),
        ];
    }
}
