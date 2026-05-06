<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Concerns\InteractsWithFormBuilder;
use Filament\FormBuilder\Contracts\HasFormBuilder;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Override;

final class CapellAgentBridgePromptBuilderPage extends Page implements HasFormBuilder
{
    use InteractsWithFormBuilder;

    /** @var array<string, mixed> */
    public array $data = [
        'safety' => 'preview_first',
    ];

    public string $preparedPrompt = '';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $slug = 'capell-agent-bridge/prompt-builder';

    protected static ?int $navigationSort = 10;

    protected string $view = 'capell-agent-bridge::filament.pages.prompt-builder';

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-agent-bridge::admin.prompt_builder_navigation');
    }

    #[Override]
    public function getTitle(): string
    {
        return __('capell-agent-bridge::admin.prompt_builder_title');
    }

    public function mount(): void
    {
        $this->getForm('form')?->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('capell-agent-bridge::admin.intent_section'))
                    ->description(__('capell-agent-bridge::admin.intent_section_description'))
                    ->schema([
                        TextInput::make('goal')
                            ->label(__('capell-agent-bridge::admin.goal'))
                            ->helperText(__('capell-agent-bridge::admin.goal_help'))
                            ->maxLength(180)
                            ->required(),
                        Grid::make(3)
                            ->schema([
                                Select::make('area')
                                    ->label(__('capell-agent-bridge::admin.area'))
                                    ->options($this->areaOptions())
                                    ->required(),
                                Select::make('operation')
                                    ->label(__('capell-agent-bridge::admin.operation'))
                                    ->options($this->operationOptions())
                                    ->required(),
                                Select::make('safety')
                                    ->label(__('capell-agent-bridge::admin.safety'))
                                    ->options($this->safetyOptions())
                                    ->required(),
                            ]),
                        Textarea::make('target')
                            ->label(__('capell-agent-bridge::admin.target'))
                            ->helperText(__('capell-agent-bridge::admin.target_help'))
                            ->rows(3),
                        Textarea::make('constraints')
                            ->label(__('capell-agent-bridge::admin.constraints'))
                            ->helperText(__('capell-agent-bridge::admin.constraints_help'))
                            ->rows(3),
                        Textarea::make('success_criteria')
                            ->label(__('capell-agent-bridge::admin.success_criteria'))
                            ->helperText(__('capell-agent-bridge::admin.success_criteria_help'))
                            ->rows(3),
                    ]),
            ]);
    }

    public function buildPrompt(): void
    {
        $state = $this->getForm('form')?->getState() ?? $this->data;
        $this->preparedPrompt = $this->promptFromState($state);

        Notification::make('capell_agent-bridge_prompt_ready')
            ->success()
            ->title(__('capell-agent-bridge::admin.prompt_ready'))
            ->send();
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('buildPrompt')
                ->label(__('capell-agent-bridge::admin.build_prompt'))
                ->icon(Heroicon::OutlinedSparkles)
                ->action('buildPrompt'),
        ];
    }

    /** @return array<string, string> */
    private function areaOptions(): array
    {
        return [
            'pages' => (string) __('capell-agent-bridge::admin.area_pages'),
            'cache' => (string) __('capell-agent-bridge::admin.area_cache'),
            'seo' => (string) __('capell-agent-bridge::admin.area_seo'),
            'redirects' => (string) __('capell-agent-bridge::admin.area_redirects'),
            'navigation' => (string) __('capell-agent-bridge::admin.area_navigation'),
            'packages' => (string) __('capell-agent-bridge::admin.area_packages'),
            'other' => (string) __('capell-agent-bridge::admin.area_other'),
        ];
    }

    /** @return array<string, string> */
    private function operationOptions(): array
    {
        return [
            'inspect' => (string) __('capell-agent-bridge::admin.operation_inspect'),
            'create' => (string) __('capell-agent-bridge::admin.operation_create'),
            'update' => (string) __('capell-agent-bridge::admin.operation_update'),
            'disable' => (string) __('capell-agent-bridge::admin.operation_disable'),
            'clear' => (string) __('capell-agent-bridge::admin.operation_clear'),
            'regenerate' => (string) __('capell-agent-bridge::admin.operation_regenerate'),
            'recommend' => (string) __('capell-agent-bridge::admin.operation_recommend'),
        ];
    }

    /** @return array<string, string> */
    private function safetyOptions(): array
    {
        return [
            'preview_first' => (string) __('capell-agent-bridge::admin.safety_preview_first'),
            'read_only' => (string) __('capell-agent-bridge::admin.safety_read_only'),
            'prepare_confirmation' => (string) __('capell-agent-bridge::admin.safety_prepare_confirmation'),
        ];
    }

    /** @param array<string, mixed> $state */
    private function promptFromState(array $state): string
    {
        $goal = (string) Arr::get($state, 'goal', '');
        $area = (string) Arr::get($state, 'area', 'other');
        $operation = (string) Arr::get($state, 'operation', 'inspect');
        $safety = (string) Arr::get($state, 'safety', 'preview_first');
        $target = trim((string) Arr::get($state, 'target', ''));
        $constraints = trim((string) Arr::get($state, 'constraints', ''));
        $successCriteria = trim((string) Arr::get($state, 'success_criteria', ''));

        return trim(sprintf(
            <<<'PROMPT'
                I want to use the Capell Site Agent Bridge server to %s.

                Area: %s
                Operation: %s
                Safety mode: %s

                Target/context:
                %s

                Constraints:
                %s

                Success criteria:
                %s

                Before taking any mutating action, list the matching Agent Bridge capability, the exact payload you plan to send, and wait for the preview/confirmation workflow.
                PROMPT,
            $goal,
            $area,
            $operation,
            $safety,
            $target !== '' ? $target : 'Not provided.',
            $constraints !== '' ? $constraints : 'Use Capell package boundaries, policies, and preview-first workflow.',
            $successCriteria !== '' ? $successCriteria : 'Explain what changed or why no change is needed.',
        ));
    }
}
