<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\RepeaterTabs;
use Capell\Admin\Filament\Components\Forms\TranslationLanguageSelect;
use Capell\Admin\Filament\Components\Forms\TranslationsRepeater;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;

final class ContentTranslationsRepeater
{
    public static function make(
        Schema $schema,
        array $components = [],
        bool $hasTitle = true,
        bool $hasContent = true,
        bool $titleRequired = true
    ): RepeaterTabs {
        $totalLanguages = (int) Cache::rememberForever(
            'languages_total',
            fn (): int => CapellCore::getModel(ModelEnum::Language)::count()
        );

        $operation = $schema->getOperation();

        return TranslationsRepeater::make('translations')
            ->when(
                $operation === 'replicate',
                fn (TranslationsRepeater $repeater): TranslationsRepeater => $repeater->withoutRelationship()
            )
            ->schema([
                ...($hasTitle ? self::getTitleSchema($titleRequired, $totalLanguages) : []),
                ...($hasContent ? self::getContentSchema() : []),
                ...$components,
            ]);
    }

    private static function getContentSchema(): array
    {
        return [
            ContentEditor::make(),
        ];
    }

    private static function getTitleSchema(bool $titleRequired, int $totalLanguages): array
    {
        return [
            Hidden::make('is_title_changed_manually')
                ->default(false)
                ->dehydrated(false),

            Grid::make($totalLanguages === 1 ? 1 : 3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('title')
                        ->label(__('capell-admin::form.title'))
                        ->required($titleRequired)
                        ->columnSpan(fn ($operation, $record): int => $totalLanguages === 1 ? 1 : 3)
                        ->afterStateUpdated(
                            function (Get $get, Set $set, $state, TextInput $component): void {
                                $namePath = '../../name';

                                $livewire = $component->getLivewire();

                                $segment = $component->generateRelativeStatePath($namePath);

                                if (! isset($livewire->$segment)) {
                                    return;
                                }

                                $set('is_title_changed_manually', (bool) $state);

                                if (! $get($namePath)) {
                                    $set($namePath, $state);

                                    return;
                                }

                                if (! $get('../../sync_name_title')) {
                                    return;
                                }

                                $set($namePath, $state);
                            }
                        ),

                    TranslationLanguageSelect::make(),
                ]),
        ];
    }
}
