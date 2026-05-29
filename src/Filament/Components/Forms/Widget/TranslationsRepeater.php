<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Capell\Admin\Actions\MutateContentPresenterAction;
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
    /**
     * @param  array<array-key, mixed>  $components
     */
    public static function make(Schema $configurator, array $components = []): RepeaterTabs
    {
        return BaseTranslationsRepeater::make('translations')
            ->when(
                $configurator->getOperation() === 'replicate',
                fn (BaseTranslationsRepeater $repeater): BaseTranslationsRepeater => $repeater->withoutRelationship(),
            )
            ->mutateRelationshipDataBeforeFillUsing(function (array $data) use ($configurator): array {
                $data['content'] = self::normalizeContentForFill(
                    $data['content'] ?? [],
                    self::getContentStructure($configurator),
                );

                return $data;
            })
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

    /**
     * @return null|string|array<int|string, mixed>
     */
    private static function normalizeContentForFill(mixed $content, ?ContentStructure $contentStructure): null|string|array
    {
        if ($contentStructure === ContentStructure::Blocks && is_string($content)) {
            $decodedContent = json_decode($content, true);

            if (is_array($decodedContent)) {
                return $decodedContent;
            }
        }

        return MutateContentPresenterAction::run($content, $contentStructure);
    }

    private static function getContentStructure(Schema $configurator): ?ContentStructure
    {
        $record = $configurator->getRecord();

        if (! $record instanceof Model) {
            return null;
        }

        $type = null;

        if ($record->relationLoaded('type')) {
            $loadedType = $record->getRelationValue('type');
            $type = $loadedType instanceof Blueprint ? $loadedType : null;
        }

        if (! $type instanceof Blueprint && $record->getAttribute('blueprint_id') !== null) {
            $type = Blueprint::query()->find($record->getAttribute('blueprint_id'));
        }

        return $type instanceof Blueprint ? $type->content_structure : null;
    }
}
