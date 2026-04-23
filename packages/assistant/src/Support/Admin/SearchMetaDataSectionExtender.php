<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Admin;

use Capell\SeoTools\Contracts\Extenders\SearchMetaDataSectionExtender as SearchMetaDataSectionExtenderContract;
use Capell\Assistant\Actions\SuggestMetaDescriptionsAction;
use Capell\Assistant\Exceptions\OpenAICircuitBreakerOpenException;
use Capell\Assistant\Settings\AssistantSettings;
use Capell\Assistant\Support\Context\ContentActionContext;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\GridDirection;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;

class SearchMetaDataSectionExtender implements SearchMetaDataSectionExtenderContract
{
    private readonly SuggestMetaDescriptionsAction $suggestMetaDescriptionsAction;

    private string $metaDescriptionSuggestionsActionName = 'generate_meta_descriptions';

    public function __construct(SuggestMetaDescriptionsAction $suggestMetaDescriptionsAction)
    {
        $this->suggestMetaDescriptionsAction = $suggestMetaDescriptionsAction;
    }

    /**
     * @return array<int, Action>
     */
    public function headerActions(Section $component): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        return [
            $this->metaDescriptionSuggestionsAction(),
        ];
    }

    private function isEnabled(): bool
    {
        $prompts = resolve(AssistantSettings::class)->prompts;

        return isset($prompts['meta_description']) && $prompts['meta_description'] === true;
    }

    private function metaDescriptionSuggestionsAction(): Action
    {
        return Action::make($this->metaDescriptionSuggestionsActionName)
            ->label(__('Generate Descriptions'))
            ->link()
            ->color('warning')
            ->icon(Heroicon::OutlinedSparkles)
            ->modalDescription(__('Provide keywords or page content to generate SEO-friendly meta description suggestions using AI.'))
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

                $keywords = $get('meta.keywords') ?? null;
                if (blank($keywords) && $site) {
                    $keywords = $site->translation->meta_keywords !== '' ? $site->translation->meta_keywords : $site->translation->title;
                }

                return [
                    'keywords' => $keywords,
                    'content' => strip_tags((string) $get('content')),
                    'currentDescription' => $get('meta.description'),
                    'includeCurrentDescription' => true,
                ];
            })
            ->schema(fn (): array => [
                Hidden::make('currentDescription'),
                Radio::make('includeCurrentDescription')
                    ->label(__('Include current description?'))
                    ->options(fn (Get $get): array => [
                        true => __('Yes, refine current description'),
                        false => __('No, generate new descriptions'),
                    ])
                    ->helperText(__('Include the current description for refinement or ignore it to get fresh suggestions.'))
                    ->columns()
                    ->gridDirection(GridDirection::Row),
                Textarea::make('keywords')
                    ->label(__('Target Keywords'))
                    ->helperText(__('Provide target keywords to help generate SEO-friendly meta descriptions.'))
                    ->rows(2)
                    ->requiredWithout('content'),
                Textarea::make('content')
                    ->label(__('Page Content'))
                    ->helperText(__('Provide the main content of the page to help generate relevant meta descriptions.'))
                    ->rows(5)
                    ->requiredWithout('keywords'),
            ])
            ->registerModalActions([
                $this->suggestedMetaDescriptionsAction(),
            ])
            ->action($this->handleMetaDescriptionSuggestionsAction(...));
    }

    private function handleMetaDescriptionSuggestionsAction(HasActions $livewire, Section $component, Action $action, array $data, ?Translation $record): void
    {
        $keywords = isset($data['keywords']) ? trim((string) $data['keywords']) : '';
        $content = isset($data['content']) ? trim((string) $data['content']) : '';
        $includeCurrent = (bool) ($data['includeCurrentDescription'] ?? false);
        $currentDescription = $data['currentDescription'] ?? ($record?->meta['description'] ?? '');

        $context = new ContentActionContext(
            content: $content,
            keywords: $keywords,
            pageId: $record?->translatable_id,
            pageType: $record?->translatable_type,
            languageId: $record?->language_id,
        );

        $options = [
            'user_id' => auth()->id(),
            'current_description' => $includeCurrent ? $currentDescription : null,
        ];

        $descriptions = [];
        try {
            $descriptions = $this->suggestMetaDescriptionsAction->run($context, $options);
        } catch (OpenAICircuitBreakerOpenException $e) {
            Notification::make('ai-suggest-meta-descriptions-error')
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
            Notification::make('ai-suggest-meta-descriptions-error')
                ->title(__('Failed to suggest meta descriptions'))
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $action->halt();
        }

        $livewire->mountAction(
            'suggested_meta_descriptions',
            arguments: ['descriptions' => $descriptions],
        );
    }

    private function suggestedMetaDescriptionsAction(): Action
    {
        return Action::make('suggested_meta_descriptions')
            ->label(__('Suggested Meta Descriptions'))
            ->modal()
            ->modalWidth('lg')
            ->icon(Heroicon::OutlinedLightBulb)
            ->schema(fn (array $arguments): array => [
                Radio::make('descriptions')
                    ->label(__('Select a Suggested Meta Description'))
                    ->options(
                        collect($arguments['descriptions'] ?? [])
                            ->mapWithKeys(fn (string $description): array => [$description => $description])
                            ->all(),
                    )
                    ->required(),
            ])
            ->cancelParentActions()
            ->modalSubmitActionLabel(__('Use Description'))
            ->action(function (Set $set, HasActions $livewire, Action $action, array $data): void {
                $set('description', $data['descriptions']);
            });
    }
}
