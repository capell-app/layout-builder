<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Support\Admin;

use Capell\Admin\Contracts\Extenders\PageTitleWithSlugInputExtender as PageTitleWithSlugInputExtenderContract;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoTools\Assistant\Actions\SuggestPageTitlesAction;
use Capell\SeoTools\Assistant\Exceptions\OpenAICircuitBreakerOpenException;
use Capell\SeoTools\Assistant\Settings\AssistantSettings;
use Capell\SeoTools\Assistant\Support\Context\ContentActionContext;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\GridDirection;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;

class PageTitleWithSlugInputExtender implements PageTitleWithSlugInputExtenderContract
{
    private readonly SuggestPageTitlesAction $suggestPageTitlesAction;

    private string $titleSuggestionsActionName = 'generate';

    public function __construct(SuggestPageTitlesAction $suggestPageTitlesAction)
    {
        $this->suggestPageTitlesAction = $suggestPageTitlesAction;
    }

    /**
     * Optionally return additional actions to register on the TitleWithSlugInput component.
     *
     * @return array<int, Action>
     */
    public function actions(): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        return [
            $this->titleSuggestionsAction(),
        ];
    }

    /**
     * Optionally return a Schema to be rendered after the component label.
     */
    public function afterLabel(FusedGroup $component): ?Schema
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return Schema::end([
            $component->getAction($this->titleSuggestionsActionName),
        ]);
    }

    private function isEnabled(): bool
    {
        $prompts = resolve(AssistantSettings::class)->prompts;

        return isset($prompts['title_generation']) && $prompts['title_generation'] === true;
    }

    private function titleSuggestionsAction(): Action
    {
        return Action::make($this->titleSuggestionsActionName)
            ->label(__('Title Suggestions'))
            ->link()
            ->color('warning')
            ->icon(Heroicon::OutlinedSparkles)
            ->modalDescription(__('Provide keywords or page content to generate SEO-friendly title suggestions using AI.'))
            ->fillForm(function (Get $get): array {
                /** @var class-string<Site> $model */
                $model = CapellCore::getModel(ModelEnum::Site);

                $site = $model::query()
                    ->whereKey($get('../../site_id'))
                    ->withWhereHas(
                        'translation',
                        fn (BuilderContract $query): BuilderContract => $query->where('language_id', $get('language_id')),
                    )
                    ->first();

                $keywords = $get('meta')['keywords'] ?? null;
                if (blank($keywords) && $site) {
                    $keywords = $site->translation->meta_keywords !== '' ? $site->translation->meta_keywords : $site->translation->title;
                }

                return [
                    'keywords' => $keywords,
                    'content' => strip_tags((string) $get('content')),
                    'title' => filled($get('title')) ? $get('title') : $get('../../name'),
                    'includeCurrentTitle' => true,
                ];
            })
            ->schema(fn (): array => [
                Hidden::make('title'),
                Radio::make('includeCurrentTitle')
                    ->label(__('Include current title?'))
                    ->options(fn (Get $get): array => [
                        true => __('Yes, include: ":title"', ['title' => $get('title')]),
                        false => __('No, ignore current title'),
                    ])
                    ->helperText(__('Include the current title for better suggestions or ignore it to get fresh ideas.'))
                    ->columns()
                    ->gridDirection(GridDirection::Row),
                Textarea::make('keywords')
                    ->label(__('Target Keywords'))
                    ->helperText(__('Provide target keywords to help generate SEO-friendly titles.'))
                    ->rows(2)
                    ->requiredWithout('content'),
                Textarea::make('content')
                    ->label(__('Page Content'))
                    ->helperText(__('Provide the main content of the page to help generate relevant titles.'))
                    ->rows(5)
                    ->requiredWithout('keywords'),
            ])
            ->registerModalActions([
                $this->suggestedTitlesAction(),
            ])
            ->action($this->handleTitleSuggestionsAction(...));
    }

    private function handleTitleSuggestionsAction(HasActions $livewire, FusedGroup $component, Action $action, array $data, ?Translation $record): void
    {
        $keywords = isset($data['keywords']) ? trim((string) $data['keywords']) : '';
        $content = isset($data['content']) ? trim((string) $data['content']) : '';
        $includeCurrent = (bool) ($data['includeCurrentTitle'] ?? false);
        $currentTitle = $data['title'] ?? ($record?->title ?? '');

        $context = new ContentActionContext(
            content: $content,
            keywords: $keywords,
            pageId: $record?->translatable_id,
            pageType: $record?->translatable_type,
            languageId: $record?->language_id,
        );

        $options = [
            'user_id' => auth()->id(),
            'current_title' => $includeCurrent ? $currentTitle : null,
        ];

        $titles = [];
        try {
            $titles = $this->suggestPageTitlesAction->run($context, $options);
        } catch (OpenAICircuitBreakerOpenException $e) {
            Notification::make('ai-suggest-titles-error')
                ->title($e->getMessage())
                ->body(__('Please try again later or clear the circuit breaker and retry.'))
                ->danger()
                ->persistent()
                ->actions([
                    Action::make('clear-ai-circuit-breaker')
                        ->label(__('Clear Circuit Breaker'))
                        ->iconPosition(IconPosition::Before)
                        ->close()
                        ->button()
                        ->color('danger')
                        ->dispatchTo($component->getLivewire()->getName(), 'clear-circuit-breaker'),
                ])
                ->send();

            $action->halt();
        } catch (Exception $e) {
            Notification::make('ai-suggest-titles-error')
                ->title(__('Failed to suggest titles'))
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $action->halt();
        }

        $livewire->mountAction(
            'suggested_titles',
            arguments: ['titles' => $titles],
        );
    }

    private function suggestedTitlesAction(): Action
    {
        return Action::make('suggested_titles')
            ->label(__('Suggested Titles'))
            ->modal()
            ->modalWidth('lg')
            ->icon(Heroicon::OutlinedLightBulb)
            ->schema(fn (array $arguments): array => [
                Radio::make('titles')
                    ->label(__('Select a Suggested Title'))
                    ->options(
                        collect($arguments['titles'] ?? [])
                            ->mapWithKeys(fn (string $title): array => [$title => $title])
                            ->all(),
                    )
                    ->required(),
            ])
            ->cancelParentActions()
            ->modalSubmitActionLabel(__('Use Title'))
            ->action(function (Set $set, HasActions $livewire, Action $action, array $data): void {
                $set('title', $data['titles']);
            });
    }
}
