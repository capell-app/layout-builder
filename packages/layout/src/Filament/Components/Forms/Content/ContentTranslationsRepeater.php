<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\RepeaterTabs;
use Capell\Admin\Filament\Components\Forms\TranslationLanguageSelect;
use Capell\Admin\Filament\Components\Forms\TranslationsRepeater;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Cache;

final class ContentTranslationsRepeater
{
    public static function make(Forms\Form $form, array $schema = [], bool $titleRequired = true): RepeaterTabs
    {
        $languages_total = (int) Cache::rememberForever(
            'languages_total',
            fn (): int => CapellCore::getModel(ModelEnum::Language)::count()
        );

        $operation = $form->getOperation();

        return TranslationsRepeater::make('translations')
            ->when(
                $operation === 'replicate',
                fn (TranslationsRepeater $repeater): TranslationsRepeater => $repeater->withoutRelationship()
            )
            ->schema([
                Forms\Components\Hidden::make('is_title_changed_manually')
                    ->default(false)
                    ->dehydrated(false),

                Forms\Components\Grid::make($languages_total === 1 ? 1 : 3)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('capell-admin::form.title'))
                            ->required($titleRequired)
                            ->columnSpan(fn ($operation, $record): int => $languages_total === 1 ? 1 : 3)
                            ->afterStateUpdated(
                                function (Get $get, Set $set, $state, Forms\Components\TextInput $component): void {
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

                ContentEditor::make(),

                ...$schema,
            ]);
    }
}
