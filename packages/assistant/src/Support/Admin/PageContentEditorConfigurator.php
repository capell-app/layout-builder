<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Admin;

use Capell\Assistant\Actions\GeneratorPageContentAction;
use Capell\Assistant\Exceptions\OpenAICircuitBreakerOpenException;
use Capell\Assistant\Settings\AssistantSettings;
use Capell\Assistant\Support\Context\ContentActionContext;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;

class PageContentEditorConfigurator
{
    public function __invoke(Field $component): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if (! method_exists($component, 'hintAction')) {
            return;
        }

        $component->hintAction($this->generateContentAction());
    }

    private function isEnabled(): bool
    {
        $prompts = resolve(AssistantSettings::class)->prompts;

        return isset($prompts['content_generation']) && $prompts['content_generation'] === true;
    }

    private function generateContentAction(): Action
    {
        return Action::make('generateContent')
            ->label(__('Generate Content'))
            ->link()
            ->color('warning')
            ->icon(Heroicon::OutlinedSparkles)
            ->modalDescription(__('Provide keywords to generate SEO-friendly content using AI.'))
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
                    'content' => strip_tags($get('content') ?? ''),
                    'title' => filled($get('title')) ? $get('title') : $get('../../name'),
                    'includeCurrentContent' => true,
                ];
            })
            ->schema(fn (): array => [
                Hidden::make('content'),
                Checkbox::make('includeCurrentContent')
                    ->label(__('Include current content?')),
                Textarea::make('keywords')
                    ->label(__('Target Keywords'))
                    ->helperText(__('Provide target keywords to help generate SEO-friendly content.'))
                    ->rows(2)
                    ->requiredWithout('title'),
                TextInput::make('title')
                    ->label(__('Page Title'))
                    ->requiredWithout('keywords'),
                TextInput::make('target_length')
                    ->label(__('Target Length (words)'))
                    ->numeric()
                    ->minValue(100)
                    ->maxValue(2000)
                    ->helperText(__('Optional. Aim for this length; the AI will adjust as needed.')),
            ])
            ->action($this->handleGenerateContentAction(...));
    }

    private function handleGenerateContentAction(Set $set, $component, Action $action, array $data, ?Translation $record): void
    {
        $keywords = isset($data['keywords']) ? trim((string) $data['keywords']) : '';
        $content = isset($data['content']) ? trim((string) $data['content']) : '';
        $includeCurrent = (bool) ($data['includeCurrentContent'] ?? false);
        $currentTitle = $data['title'] ?? ($record?->title ?? '');
        $targetLength = isset($data['target_length']) && $data['target_length'] !== ''
            ? max(100, min(2000, (int) $data['target_length']))
            : null;

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
            'target_length' => $targetLength,
            'refactor' => $includeCurrent,
        ];

        $generated = null;
        try {
            $generated = GeneratorPageContentAction::run($context, $options);
        } catch (OpenAICircuitBreakerOpenException $e) {
            Notification::make('ai-generate-content-error')
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
            Notification::make('ai-generate-content-error')
                ->title(__('Failed to generate content'))
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $action->halt();
        }

        if (is_string($generated)) {
            $set('content', $generated);
        }
    }
}
