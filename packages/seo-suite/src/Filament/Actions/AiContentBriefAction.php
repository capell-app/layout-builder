<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\GenerateAiContentBriefAction;
use Capell\SeoSuite\Data\AiContentBriefData;
use Capell\SeoSuite\Filament\Components\Forms\Page\PageSeoPanel;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\FormBuilder\Components\Hidden;
use Filament\FormBuilder\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Throwable;

class AiContentBriefAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('capell-seo-suite::generic.ai_content_brief_action'))
            ->icon(Heroicon::OutlinedSparkles)
            ->color('warning')
            ->modalHeading(__('capell-seo-suite::generic.ai_content_brief_modal_heading'))
            ->modalDescription(__('capell-seo-suite::generic.ai_content_brief_modal_description'))
            ->modalSubmitActionLabel(__('capell-seo-suite::generic.ai_content_brief_generate'))
            ->schema(fn (Get $get): array => [
                Hidden::make('language_id')
                    ->default($get('language_id')),
                Placeholder::make('readonly_notice')
                    ->label(__('capell-seo-suite::generic.ai_content_brief_readonly_notice_label'))
                    ->content(__('capell-seo-suite::generic.ai_content_brief_readonly_notice')),
            ])
            ->registerModalActions([
                $this->resultsAction(),
            ])
            ->action($this->generateBrief(...));
    }

    public static function getDefaultName(): ?string
    {
        return 'ai_content_brief';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function generateBrief(HasActions $livewire, PageSeoPanel $component, Action $action, array $data): void
    {
        $context = $component->resolveAiContentBriefContext($data['language_id'] ?? null);

        if ($context === null) {
            Notification::make('ai-content-brief-missing-context')
                ->title(__('capell-seo-suite::generic.ai_content_brief_missing_context'))
                ->warning()
                ->send();

            $action->halt();
        }

        try {
            /** @var Page $page */
            $page = $context['page'];
            /** @var Site $site */
            $site = $context['site'];
            /** @var Language $language */
            $language = $context['language'];

            $brief = GenerateAiContentBriefAction::run($page, $site, $language);

            $livewire->mountAction(
                'ai_content_brief_results',
                arguments: ['brief' => $brief->toArray()],
            );
        } catch (Throwable $throwable) {
            Notification::make('ai-content-brief-error')
                ->title(__('capell-seo-suite::generic.ai_content_brief_failed'))
                ->body($throwable->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $action->halt();
        }
    }

    private function resultsAction(): Action
    {
        return Action::make('ai_content_brief_results')
            ->label(__('capell-seo-suite::generic.ai_content_brief_results'))
            ->modal()
            ->modalWidth('4xl')
            ->modalHeading(__('capell-seo-suite::generic.ai_content_brief_results'))
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Action $action): Action => $action->label(__('capell-seo-suite::generic.ai_content_brief_close')))
            ->schema(fn (array $arguments): array => $this->resultsSchema(AiContentBriefData::from($arguments['brief'] ?? [])))
            ->cancelParentActions();
    }

    /**
     * @return array<int, Placeholder>
     */
    private function resultsSchema(AiContentBriefData $brief): array
    {
        return [
            Placeholder::make('content_angle')
                ->label(__('capell-seo-suite::generic.ai_content_brief_content_angle'))
                ->content($brief->contentAngle),
            $this->listPlaceholder('missing_topics', __('capell-seo-suite::generic.ai_content_brief_missing_topics'), $brief->missingTopics),
            $this->listPlaceholder('suggested_headings', __('capell-seo-suite::generic.ai_content_brief_suggested_headings'), $brief->suggestedHeadings),
            $this->listPlaceholder('faq_ideas', __('capell-seo-suite::generic.ai_content_brief_faq_ideas'), $brief->faqIdeas),
            $this->listPlaceholder('schema_opportunities', __('capell-seo-suite::generic.ai_content_brief_schema_opportunities'), $brief->schemaOpportunities),
            $this->listPlaceholder('internal_links', __('capell-seo-suite::generic.ai_content_brief_internal_links'), $brief->internalLinks),
            $this->listPlaceholder('meta_title_alternatives', __('capell-seo-suite::generic.ai_content_brief_meta_title_alternatives'), $brief->metaTitleAlternatives),
            $this->listPlaceholder('meta_description_alternatives', __('capell-seo-suite::generic.ai_content_brief_meta_description_alternatives'), $brief->metaDescriptionAlternatives),
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function listPlaceholder(string $name, string $label, array $items): Placeholder
    {
        return Placeholder::make($name)
            ->label($label)
            ->content($this->listHtml($items));
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function listHtml(array $items): HtmlString
    {
        if ($items === []) {
            return new HtmlString('<span class="text-gray-500">' . e(__('capell-seo-suite::generic.ai_content_brief_no_suggestions')) . '</span>');
        }

        $listItems = collect($items)
            ->map(fn (mixed $item): string => '<li>' . e($this->itemText($item)) . '</li>')
            ->implode('');

        return new HtmlString('<ul class="list-disc space-y-1 ps-5">' . $listItems . '</ul>');
    }

    private function itemText(mixed $item): string
    {
        if (is_array($item)) {
            return collect($item)
                ->map(fn (mixed $value, string|int $key): string => is_string($key)
                    ? sprintf('%s: %s', str($key)->headline()->toString(), $this->itemText($value))
                    : $this->itemText($value))
                ->implode(' | ');
        }

        if (is_bool($item)) {
            return $item ? 'true' : 'false';
        }

        return is_scalar($item) ? (string) $item : '';
    }
}
