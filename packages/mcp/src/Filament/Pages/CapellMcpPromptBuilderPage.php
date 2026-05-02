<?php

declare(strict_types=1);

namespace Capell\Mcp\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Override;

final class CapellMcpPromptBuilderPage extends Page implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed> */
    public array $data = [
        'safety' => 'preview_first',
    ];

    public string $preparedPrompt = '';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $slug = 'capell-mcp/prompt-builder';

    protected static ?int $navigationSort = 10;

    protected string $view = 'capell-mcp::filament.pages.prompt-builder';

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-mcp::admin.prompt_builder_navigation');
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-mcp::admin.prompt_builder_title');
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
                Section::make(__('capell-mcp::admin.intent_section'))
                    ->description(__('capell-mcp::admin.intent_section_description'))
                    ->schema([
                        TextInput::make('goal')
                            ->label(__('capell-mcp::admin.goal'))
                            ->helperText(__('capell-mcp::admin.goal_help'))
                            ->maxLength(180)
                            ->required(),
                        Grid::make(3)
                            ->schema([
                                Select::make('area')
                                    ->label(__('capell-mcp::admin.area'))
                                    ->options($this->areaOptions())
                                    ->required(),
                                Select::make('operation')
                                    ->label(__('capell-mcp::admin.operation'))
                                    ->options($this->operationOptions())
                                    ->required(),
                                Select::make('safety')
                                    ->label(__('capell-mcp::admin.safety'))
                                    ->options($this->safetyOptions())
                                    ->required(),
                            ]),
                        Textarea::make('target')
                            ->label(__('capell-mcp::admin.target'))
                            ->helperText(__('capell-mcp::admin.target_help'))
                            ->rows(3),
                        Textarea::make('constraints')
                            ->label(__('capell-mcp::admin.constraints'))
                            ->helperText(__('capell-mcp::admin.constraints_help'))
                            ->rows(3),
                        Textarea::make('success_criteria')
                            ->label(__('capell-mcp::admin.success_criteria'))
                            ->helperText(__('capell-mcp::admin.success_criteria_help'))
                            ->rows(3),
                    ]),
            ]);
    }

    public function buildPrompt(): void
    {
        $state = $this->getForm('form')?->getState() ?? $this->data;
        $this->preparedPrompt = $this->promptFromState($state);

        Notification::make('capell_mcp_prompt_ready')
            ->success()
            ->title(__('capell-mcp::admin.prompt_ready'))
            ->send();
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('buildPrompt')
                ->label(__('capell-mcp::admin.build_prompt'))
                ->icon(Heroicon::OutlinedSparkles)
                ->action('buildPrompt'),
        ];
    }

    /** @return array<string, string> */
    private function areaOptions(): array
    {
        return [
            'pages' => (string) __('capell-mcp::admin.area_pages'),
            'cache' => (string) __('capell-mcp::admin.area_cache'),
            'seo' => (string) __('capell-mcp::admin.area_seo'),
            'redirects' => (string) __('capell-mcp::admin.area_redirects'),
            'navigation' => (string) __('capell-mcp::admin.area_navigation'),
            'packages' => (string) __('capell-mcp::admin.area_packages'),
            'other' => (string) __('capell-mcp::admin.area_other'),
        ];
    }

    /** @return array<string, string> */
    private function operationOptions(): array
    {
        return [
            'inspect' => (string) __('capell-mcp::admin.operation_inspect'),
            'create' => (string) __('capell-mcp::admin.operation_create'),
            'update' => (string) __('capell-mcp::admin.operation_update'),
            'disable' => (string) __('capell-mcp::admin.operation_disable'),
            'clear' => (string) __('capell-mcp::admin.operation_clear'),
            'regenerate' => (string) __('capell-mcp::admin.operation_regenerate'),
            'recommend' => (string) __('capell-mcp::admin.operation_recommend'),
        ];
    }

    /** @return array<string, string> */
    private function safetyOptions(): array
    {
        return [
            'preview_first' => (string) __('capell-mcp::admin.safety_preview_first'),
            'read_only' => (string) __('capell-mcp::admin.safety_read_only'),
            'prepare_confirmation' => (string) __('capell-mcp::admin.safety_prepare_confirmation'),
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
                I want to use the Capell Site MCP server to %s.

                Area: %s
                Operation: %s
                Safety mode: %s

                Target/context:
                %s

                Constraints:
                %s

                Success criteria:
                %s

                Before taking any mutating action, list the matching MCP capability, the exact payload you plan to send, and wait for the preview/confirmation workflow.
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
