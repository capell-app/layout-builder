<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Actions;

use Capell\SeoTools\Actions\GenerateAiLayoutAction;
use Capell\SeoTools\Actions\SubmitAiCreatorDraftAction;
use Capell\SeoTools\DataObjects\AiCreatorData;
use Capell\SeoTools\Models\AiCreatorContext;
use Capell\SeoTools\Models\AiCreatorSession;
use Capell\SeoTools\Policies\AiCreatorPolicy;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class AiCreatorAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('ai-creator')
            ->label('AI Creator')
            ->icon('heroicon-o-sparkles')
            ->slideOver()
            ->visible(fn (): bool => resolve(AiCreatorPolicy::class)->isEnabledFor(
                $this->resolveSiteFromRecord(),
            ))
            ->form(fn (): array => $this->buildWizardForm())
            ->action(function (array $data): void {
                $this->runCreator($data);
            });
    }

    private function buildWizardForm(): array
    {
        return [
            Hidden::make('ai_session_id'),

            Wizard::make([
                Step::make('Describe')
                    ->label('What are we building?')
                    ->schema([
                        Textarea::make('intent')
                            ->label('Describe the page you want to create')
                            ->placeholder('e.g. A homepage for a law firm with a hero, services section, and contact form')
                            ->required()
                            ->rows(4),
                        Select::make('page_count')
                            ->label('How many pages?')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                            ->default(1)
                            ->visible(fn (): bool => $this->isMountedOnSiteResource()),
                    ]),

                Step::make('Brand')
                    ->label('Brand & tone')
                    ->schema(fn (): array => $this->buildBrandStep())
                    ->afterValidation(function (Get $get, Set $set): void {
                        $this->generateLayout($get, $set);
                    }),

                Step::make('Layout')
                    ->label('Proposed layout')
                    ->schema([
                        Repeater::make('layout_preview')
                            ->label('AI-proposed sections (reorder or remove as needed)')
                            ->schema([
                                TextInput::make('section_type')->label('Section type')->disabled(),
                                Textarea::make('fields_preview')->label('Fields preview')->disabled()->rows(2),
                            ])
                            ->addable(false)
                            ->reorderable()
                            ->columns(2),
                    ]),

                Step::make('Review')
                    ->label('Review & submit')
                    ->schema([
                        Textarea::make('review_notes')
                            ->label('Notes for reviewer (optional)')
                            ->rows(3),
                    ]),
            ])->submitAction(
                Action::make('submit')
                    ->label('Submit for Review')
                    ->color('primary'),
            ),
        ];
    }

    private function buildBrandStep(): array
    {
        $siteId = $this->resolveSiteId();
        $existingContext = $siteId ? AiCreatorContext::query()->where('site_id', $siteId)->first() : null;

        return [
            Select::make('tone')
                ->label('Tone of voice')
                ->options([
                    'professional' => 'Professional & formal',
                    'friendly' => 'Warm & approachable',
                    'playful' => 'Fun & playful',
                    'authoritative' => 'Authoritative & expert',
                ])
                ->default($existingContext?->tone ?? 'professional')
                ->required(),

            TextInput::make('industry')
                ->label('Industry / sector')
                ->default($existingContext?->industry ?? '')
                ->placeholder('e.g. Legal, Healthcare, E-commerce'),

            Textarea::make('target_audience')
                ->label('Target audience')
                ->default($existingContext?->target_audience ?? '')
                ->placeholder('e.g. Small business owners aged 30-50')
                ->rows(2),

            Textarea::make('brand_voice_notes')
                ->label('Brand voice notes (optional)')
                ->default($existingContext?->brand_voice_notes ?? '')
                ->placeholder('e.g. We never use jargon. Always end with a call to action.')
                ->rows(2),
        ];
    }

    private function generateLayout(Get $get, Set $set): void
    {
        $siteId = $this->resolveSiteId() ?? 0;
        $userId = (int) Auth::id();

        AiCreatorContext::query()->updateOrCreate(['site_id' => $siteId], [
            'tone' => $get('tone') ?? 'professional',
            'industry' => $get('industry') ?? '',
            'target_audience' => $get('target_audience') ?? null,
            'brand_voice_notes' => $get('brand_voice_notes') ?? null,
        ]);

        try {
            $creatorData = new AiCreatorData(
                siteId: $siteId,
                userId: $userId,
                intent: (string) $get('intent'),
                pageCount: (int) ($get('page_count') ?? 1),
                tone: $get('tone') ?? null,
                industry: $get('industry') ?? null,
                targetAudience: $get('target_audience') ?? null,
                brandVoiceNotes: $get('brand_voice_notes') ?? null,
            );

            $sections = resolve(GenerateAiLayoutAction::class)->handle($creatorData);

            $session = AiCreatorSession::query()->where([
                'site_id' => $siteId,
                'user_id' => $userId,
                'status' => 'review',
            ])->latest()->first();

            throw_unless($session, RuntimeException::class, 'AI session was not created. Please try again.');

            $set('ai_session_id', $session->id);

            $previewData = array_map(fn (array $s): array => [
                'section_type' => $s['section_type'] ?? '',
                'fields_preview' => json_encode($s['fields'] ?? [], JSON_PRETTY_PRINT),
            ], $sections);

            $set('layout_preview', $previewData);
        } catch (Throwable $throwable) {
            Notification::make()
                ->title('AI generation failed')
                ->body($throwable->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }

    private function runCreator(array $data): void
    {
        $sessionId = $data['ai_session_id'] ?? null;
        $session = $sessionId ? AiCreatorSession::query()->find((int) $sessionId) : null;

        if (! $session) {
            Notification::make()
                ->title('AI Creator failed')
                ->body('No AI session found. Please re-run the wizard.')
                ->danger()
                ->send();

            return;
        }

        try {
            resolve(SubmitAiCreatorDraftAction::class)->handle($session);

            Notification::make()
                ->title('Layout submitted for review')
                ->body('Your AI-generated layout has been sent to the workspace for approval.')
                ->success()
                ->send();
        } catch (Throwable $throwable) {
            Notification::make()
                ->title('AI Creator failed')
                ->body($throwable->getMessage())
                ->danger()
                ->send();
        }
    }

    private function resolveSiteFromRecord(): object
    {
        $record = $this->getRecord();

        if ($record !== null && method_exists($record, 'getSite')) {
            return $record->getSite();
        }

        return (object) ['ai_creator_enabled' => null];
    }

    private function resolveSiteId(): ?int
    {
        $record = $this->getRecord();

        if ($record === null) {
            return null;
        }

        if (method_exists($record, 'getSiteId')) {
            return $record->getSiteId();
        }

        if (isset($record->site_id)) {
            return (int) $record->site_id;
        }

        if (isset($record->id) && str_contains($record::class, 'Site')) {
            return (int) $record->id;
        }

        return null;
    }

    private function isMountedOnSiteResource(): bool
    {
        $record = $this->getRecord();

        return $record !== null && str_contains($record::class, 'Site');
    }
}
