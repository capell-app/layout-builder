<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Filament\Pages;

use BackedEnum;
use Capell\Admin\Filament\Pages\ExtensionsPage;
use Capell\TranslationManager\Actions\CreateLocaleFilesAction;
use Capell\TranslationManager\Actions\DuplicateLocaleAction;
use Capell\TranslationManager\Actions\ListInstalledLocalesAction;
use Capell\TranslationManager\Actions\ListTranslationFilesAction;
use Capell\TranslationManager\Actions\ListTranslationSourcesAction;
use Capell\TranslationManager\Actions\LoadTranslationComparisonAction;
use Capell\TranslationManager\Actions\SaveTranslationEntriesAction;
use Capell\TranslationManager\Actions\TranslateSelectedEntriesAction;
use Capell\TranslationManager\Contracts\TranslationAITranslator;
use Capell\TranslationManager\Data\TranslationEntryData;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

final class TranslationManagerPage extends Page
{
    public ?string $sourceKey = null;

    public string $sourceLocale = 'en';

    public ?string $targetLocale = null;

    public ?string $fileKey = null;

    public string $filter = 'all';

    /** @var array<int, array{key: string, label: string}> */
    public array $sources = [];

    /** @var array<int, array{locale: string, fileCount: int, sourceAvailable: bool, overrideAvailable: bool}> */
    public array $locales = [];

    /** @var array<int, array{key: string, label: string, type: string, relativePath: string}> */
    public array $files = [];

    /** @var array<int, array{key: string, sourceValue: string|null, targetValue: string|null, status: string, editable: bool}> */
    public array $entries = [];

    /** @var array<int, string> */
    public array $selectedEntryKeys = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?string $slug = 'translation-manager';

    protected static ?int $navigationSort = 91;

    protected string $view = 'capell-translation-manager::filament.pages.translation-manager';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-translation-manager::package.navigation_label');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_system');
    }

    #[Override]
    public static function canAccess(): bool
    {
        return ExtensionsPage::canManageExtensions();
    }

    #[Override]
    public function getTitle(): string
    {
        return (string) __('capell-translation-manager::package.title');
    }

    public function mount(): void
    {
        $configuredSourceLocale = config('capell-translation-manager.source_locale', 'en');
        $this->sourceLocale = is_string($configuredSourceLocale) ? $configuredSourceLocale : 'en';
        $this->loadSources();
        $this->sourceKey = $this->sources[0]['key'] ?? null;
        $this->refreshBrowser();
    }

    public function updatedSourceKey(): void
    {
        $this->fileKey = null;
        $this->targetLocale = null;
        $this->refreshBrowser();
    }

    public function updatedSourceLocale(): void
    {
        $this->refreshFiles();
        $this->loadEntries();
    }

    public function updatedTargetLocale(): void
    {
        $this->refreshFiles();
        $this->loadEntries();
    }

    public function updatedFileKey(): void
    {
        $this->loadEntries();
    }

    public function refreshBrowser(): void
    {
        $this->refreshLocales();
        $this->refreshFiles();
        $this->loadEntries();
    }

    public function saveTranslations(): void
    {
        if ($this->sourceKey === null || $this->fileKey === null || $this->targetLocale === null) {
            return;
        }

        SaveTranslationEntriesAction::run(
            $this->sourceKey,
            $this->fileKey,
            $this->targetLocale,
            collect($this->entries)
                ->filter(fn (array $entry): bool => $entry['editable'])
                ->mapWithKeys(fn (array $entry): array => [$entry['key'] => $entry['targetValue']])
                ->all(),
        );

        $this->loadEntries();

        Notification::make()
            ->title(__('capell-translation-manager::package.saved'))
            ->success()
            ->send();
    }

    /**
     * @return array<int, array{key: string, sourceValue: string|null, targetValue: string|null, status: string, editable: bool}>
     */
    public function filteredEntries(): array
    {
        if ($this->filter === 'all') {
            return $this->entries;
        }

        return collect($this->entries)
            ->filter(fn (array $entry): bool => $entry['status'] === $this->filter)
            ->values()
            ->all();
    }

    public function aiAvailable(): bool
    {
        return resolve(TranslationAITranslator::class)->available();
    }

    /**
     * @return array<string, string>
     */
    public function localeOptions(): array
    {
        return collect($this->locales)
            ->mapWithKeys(fn (array $locale): array => [$locale['locale'] => $locale['locale']])
            ->all();
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createLocale')
                ->label(__('capell-translation-manager::package.create_locale'))
                ->icon(Heroicon::OutlinedPlusCircle)
                ->schema([
                    TextInput::make('locale')
                        ->label(__('capell-translation-manager::package.locale'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    if ($this->sourceKey === null) {
                        return;
                    }

                    CreateLocaleFilesAction::run($this->sourceKey, (string) $data['locale'], $this->sourceLocale);
                    $this->targetLocale = (string) $data['locale'];
                    $this->refreshBrowser();

                    Notification::make()
                        ->title(__('capell-translation-manager::package.locale_created'))
                        ->success()
                        ->send();
                }),
            Action::make('duplicateLocale')
                ->label(__('capell-translation-manager::package.duplicate_locale'))
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->schema([
                    Select::make('from_locale')
                        ->label(__('capell-translation-manager::package.from_locale'))
                        ->options(fn (): array => $this->localeOptions())
                        ->required(),
                    TextInput::make('target_locale')
                        ->label(__('capell-translation-manager::package.target_locale'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    if ($this->sourceKey === null) {
                        return;
                    }

                    DuplicateLocaleAction::run($this->sourceKey, (string) $data['from_locale'], (string) $data['target_locale']);
                    $this->targetLocale = (string) $data['target_locale'];
                    $this->refreshBrowser();

                    Notification::make()
                        ->title(__('capell-translation-manager::package.locale_duplicated'))
                        ->success()
                        ->send();
                }),
            Action::make('translateSelected')
                ->label(__('capell-translation-manager::package.translate_selected'))
                ->icon(Heroicon::OutlinedSparkles)
                ->visible(fn (): bool => $this->aiAvailable())
                ->action(function (): void {
                    $this->translateSelectedEntries();
                }),
            Action::make('saveTranslations')
                ->label(__('capell-translation-manager::package.save'))
                ->icon(Heroicon::OutlinedCloudArrowUp)
                ->action(function (): void {
                    $this->saveTranslations();
                }),
        ];
    }

    private function loadSources(): void
    {
        $this->sources = collect(ListTranslationSourcesAction::run())
            ->map(fn ($source): array => [
                'key' => $source->key,
                'label' => $source->label,
            ])
            ->values()
            ->all();
    }

    private function refreshLocales(): void
    {
        if ($this->sourceKey === null) {
            $this->locales = [];

            return;
        }

        $this->locales = collect(ListInstalledLocalesAction::run($this->sourceKey))
            ->map(fn ($locale): array => [
                'locale' => $locale->locale,
                'fileCount' => $locale->fileCount,
                'sourceAvailable' => $locale->sourceAvailable,
                'overrideAvailable' => $locale->overrideAvailable,
            ])
            ->values()
            ->all();

        $localeNames = array_column($this->locales, 'locale');

        if ($this->targetLocale === null || ! in_array($this->targetLocale, $localeNames, true)) {
            $this->targetLocale = collect($localeNames)->first(fn (string $locale): bool => $locale !== $this->sourceLocale) ?? $this->sourceLocale;
        }
    }

    private function refreshFiles(): void
    {
        if ($this->sourceKey === null || $this->targetLocale === null) {
            $this->files = [];
            $this->fileKey = null;

            return;
        }

        $this->files = collect(ListTranslationFilesAction::run($this->sourceKey, $this->sourceLocale, $this->targetLocale))
            ->map(fn ($file): array => [
                'key' => $file->key,
                'label' => $file->label,
                'type' => $file->type,
                'relativePath' => $file->relativePath,
            ])
            ->values()
            ->all();

        $fileKeys = array_column($this->files, 'key');

        if ($this->fileKey === null || ! in_array($this->fileKey, $fileKeys, true)) {
            $this->fileKey = $this->files[0]['key'] ?? null;
        }
    }

    private function loadEntries(): void
    {
        $this->selectedEntryKeys = [];

        if ($this->sourceKey === null || $this->fileKey === null || $this->targetLocale === null) {
            $this->entries = [];

            return;
        }

        $this->entries = collect(LoadTranslationComparisonAction::run($this->sourceKey, $this->fileKey, $this->sourceLocale, $this->targetLocale))
            ->map(fn (TranslationEntryData $entry): array => [
                'key' => $entry->key,
                'sourceValue' => $entry->sourceValue,
                'targetValue' => $entry->targetValue,
                'status' => $entry->status,
                'editable' => $entry->editable,
            ])
            ->values()
            ->all();
    }

    private function translateSelectedEntries(): void
    {
        if ($this->targetLocale === null) {
            return;
        }

        $entryData = collect($this->entries)
            ->map(fn (array $entry): TranslationEntryData => new TranslationEntryData(
                key: $entry['key'],
                sourceValue: is_string($entry['sourceValue']) ? $entry['sourceValue'] : null,
                targetValue: is_string($entry['targetValue']) ? $entry['targetValue'] : null,
                status: $entry['status'],
                editable: $entry['editable'],
            ))
            ->all();

        $suggestions = TranslateSelectedEntriesAction::run($this->sourceLocale, $this->targetLocale, $entryData, $this->selectedEntryKeys);
        $suggestionsByKey = collect($suggestions)->keyBy(fn ($suggestion): string => $suggestion->key);

        foreach ($this->entries as $index => $entry) {
            $suggestion = $suggestionsByKey->get($entry['key']);

            if ($suggestion === null) {
                continue;
            }

            $this->entries[$index]['targetValue'] = $suggestion->value;
        }

        Notification::make()
            ->title(__('capell-translation-manager::package.translated'))
            ->success()
            ->send();
    }
}
