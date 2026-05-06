<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\SeoSuite\Actions\DashboardReports\BuildTranslationCoverageQueryAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TranslationCoverageTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildTranslationCoverageQueryAction::run())
            ->columns([
                PageNameColumn::make('name')
                    ->label(__('capell-admin::table.page'))
                    ->size('sm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.default_language')
                    ->label(__('capell-admin::table.primary_language'))
                    ->size('sm')
                    ->sortable(),
                TextColumn::make('language_completeness')
                    ->label(__('capell-admin::table.language_completeness'))
                    ->size('sm')
                    ->formatStateUsing(fn (Page $record): string => self::calculateCoverage($record) . '%')
                    ->sortable(),
                TextColumn::make('missing_languages')
                    ->label(__('capell-admin::table.missing_languages'))
                    ->size('sm')
                    ->formatStateUsing(fn (Page $record): string => implode(', ', self::getMissingLanguages($record)))
                    ->html(),
                TextColumn::make('user.name')
                    ->label(__('capell-admin::table.author'))
                    ->size('sm')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function calculateCoverage(Page $record): int
    {
        $record->loadMissing(['site.languages', 'translations']);

        $siteLanguageCount = $record->site->languages->count();

        if ($siteLanguageCount === 0) {
            return 0;
        }

        $translatedLanguageIds = $record->translations->pluck('language_id')->unique()->values();
        $covered = $record->site->languages->filter(
            fn (Language $language): bool => $translatedLanguageIds->contains($language->id),
        )->count();

        return (int) round(($covered / $siteLanguageCount) * 100);
    }

    /** @return array<int, string> */
    private static function getMissingLanguages(Page $record): array
    {
        $record->loadMissing(['site.languages', 'translations']);

        $translatedLanguageIds = $record->translations->pluck('language_id')->unique()->values();

        return $record->site->languages
            ->reject(fn (Language $language): bool => $translatedLanguageIds->contains($language->id))
            ->pluck('name')
            ->all();
    }
}
