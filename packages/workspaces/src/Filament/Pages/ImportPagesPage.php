<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Backup\Actions\InstallBackupPermissionsAction;
use Capell\Backup\Enums\ImportSessionStatus;
use Capell\Backup\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\Workspaces\Actions\Imports\AdvancePageImportToValidationAction;
use Capell\Workspaces\Actions\Imports\DispatchPageImportAction;
use Capell\Workspaces\Actions\Imports\RefreshPageImportStatusAction;
use Capell\Workspaces\Actions\Imports\StartPageImportAction;
use Capell\Workspaces\Data\Imports\PageImportDecisionData;
use Capell\Workspaces\Data\Imports\PageImportStatusData;
use Capell\Workspaces\Data\Imports\PageImportWizardStateData;
use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Override;
use Throwable;

class ImportPagesPage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    /** @var string */
    public const STEP_UPLOAD = 'upload';

    /** @var string */
    public const STEP_REVIEW = 'review';

    /** @var string */
    public const STEP_RESOLVE = 'resolve';

    /** @var string */
    public const STEP_VALIDATE = 'validate';

    /** @var string */
    public const STEP_EXECUTING = 'executing';

    /** @var string */
    public const STEP_COMPLETED = 'completed';

    /** @var string */
    public const STEP_FAILED = 'failed';

    /**
     * @deprecated Use STEP_EXECUTING. Retained for backwards compatibility.
     *
     * @var string
     */
    public const STEP_DISPATCHED = self::STEP_EXECUTING;

    /** @var array<string, mixed> */
    public array $data = [];

    public string $step = self::STEP_UPLOAD;

    public ?string $sessionStatus = null;

    /** @var array<string, mixed> */
    public array $resultSummary = [];

    public ?string $failureReason = null;

    public ?int $targetWorkspaceId = null;

    /** @var list<array<string, mixed>> */
    public array $reviewRows = [];

    /** @var array<string, array{action: string, notes?: string}> */
    public array $pageDecisions = [];

    /** @var list<array<string, mixed>> */
    public array $resolveRows = [];

    /** @var array<string, array{action: string, target_id?: int|string|null, notes?: string}> */
    public array $relationDecisions = [];

    public ?int $sessionId = null;

    /** @var array<string, mixed> */
    public array $validationSummary = [];

    public string $confirmation = '';

    public string $confirmationExpected = '';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ArrowDownTray;

    protected static ?string $slug = 'recovery-center/import-pages';

    protected string $view = 'capell-admin::components.pages.import-pages';

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::exchanger.import_pages');
    }

    /** @return array<NavigationItem> */
    public function getSubNavigation(): array
    {
        return ImportSessionResource::getSubNavigation();
    }

    public function mount(): void
    {
        $this->getForm('form')?->fill();
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-admin::exchanger.import_pages');
    }

    public function form(Schema $configurator): Schema
    {
        return $configurator
            ->statePath('data')
            ->components([
                Section::make(__('capell-admin::exchanger.upload_package'))
                    ->description(__('capell-admin::exchanger.upload_package_description'))
                    ->schema([
                        FileUpload::make('archive')
                            ->label(__('capell-admin::exchanger.package_archive'))
                            ->disk('local')
                            ->directory('exchanger/imports')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->preserveFilenames()
                            ->required()
                            ->storeFileNamesIn('archive_filename'),
                        TextInput::make('workspace_name')
                            ->label(__('capell-admin::exchanger.workspace_name'))
                            ->helperText(__('capell-admin::exchanger.workspace_name_help'))
                            ->maxLength(120)
                            ->required(),
                        TextInput::make('note')
                            ->label(__('capell-admin::exchanger.note'))
                            ->maxLength(255),
                    ]),
            ]);
    }

    /**
     * Legacy single-shot handler, retained for backwards compatibility. New
     * wizard uses {@see parseAndAdvance()} + {@see dispatchImport()}.
     */
    public function import(): void
    {
        $this->parseAndAdvance();

        if ($this->step !== self::STEP_REVIEW) {
            return;
        }

        $this->advanceToResolve();
    }

    public function parseAndAdvance(): void
    {
        try {
            $this->applyWizardState(StartPageImportAction::run($this->data));
        } catch (Throwable $throwable) {
            if ($throwable->getMessage() === StartPageImportAction::ERROR_UPLOAD_REQUIRED) {
                Notification::make()->danger()->title(__('capell-admin::exchanger.upload_required'))->send();

                return;
            }

            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.import_failed'))
                ->body($throwable->getMessage())
                ->send();
        }
    }

    /**
     * Move from review → resolve. Skips straight past resolve when the
     * resolution map is trivial (no rows surfaced at all).
     */
    public function advanceToResolve(): void
    {
        if ($this->sessionId === null) {
            return;
        }

        try {
            $this->applyWizardState(
                AdvancePageImportToValidationAction::run($this->decisionData(), false),
            );
        } catch (Throwable $throwable) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.import_failed'))
                ->body($throwable->getMessage())
                ->send();
        }
    }

    public function backToReview(): void
    {
        $this->step = self::STEP_REVIEW;
    }

    public function backToResolve(): void
    {
        if ($this->decisionData()->shouldSkipResolveStep()) {
            $this->step = self::STEP_REVIEW;

            return;
        }

        $this->step = self::STEP_RESOLVE;
    }

    /**
     * Re-run the resolver outputs against decisions, persist the dry-run
     * summary + sanitized decisions, and land on the Validate step.
     */
    public function advanceToValidate(): void
    {
        if ($this->sessionId === null) {
            return;
        }

        try {
            $this->applyWizardState(
                AdvancePageImportToValidationAction::run($this->decisionData(), true),
            );
        } catch (Throwable $throwable) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.import_failed'))
                ->body($throwable->getMessage())
                ->send();
        }
    }

    public function dispatchImport(): void
    {
        if ($this->sessionId === null) {
            return;
        }

        if ($this->step !== self::STEP_VALIDATE) {
            $this->advanceToValidate();

            if ($this->step !== self::STEP_VALIDATE) {
                return;
            }
        }

        $this->applyStatus(
            DispatchPageImportAction::run(
                sessionId: $this->sessionId,
                validationSummary: $this->validationSummary,
                confirmation: $this->confirmation,
                confirmationExpected: $this->confirmationExpected,
            ),
        );
    }

    /**
     * Poll callback for the executing step. Re-reads the session and flips
     * the wizard to the terminal step once the job finishes.
     */
    public function refreshStatus(): void
    {
        if ($this->sessionId === null) {
            return;
        }

        if ($this->step !== self::STEP_EXECUTING) {
            return;
        }

        $this->applyStatus(
            RefreshPageImportStatusAction::run($this->sessionId, $this->targetWorkspaceId),
        );
    }

    public function getProgressPercent(): int
    {
        return match ($this->sessionStatus) {
            ImportSessionStatus::Queued->value => 5,
            ImportSessionStatus::Running->value => 50,
            ImportSessionStatus::Completed->value, ImportSessionStatus::Failed->value => 100,
            default => 0,
        };
    }

    public function getTargetWorkspaceUrl(): ?string
    {
        if ($this->targetWorkspaceId === null) {
            return null;
        }

        try {
            return WorkspaceResource::getUrl('compare', [
                'record' => $this->targetWorkspaceId,
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    public function confirmationMatches(): bool
    {
        if ($this->confirmationExpected === '') {
            return true;
        }

        return mb_strtolower(trim($this->confirmation)) === mb_strtolower(trim($this->confirmationExpected));
    }

    public function backToUpload(): void
    {
        $this->step = self::STEP_UPLOAD;
        $this->reviewRows = [];
        $this->pageDecisions = [];
        $this->resolveRows = [];
        $this->relationDecisions = [];
        $this->validationSummary = [];
        $this->confirmation = '';
        $this->confirmationExpected = '';
        $this->sessionId = null;
        $this->sessionStatus = null;
        $this->resultSummary = [];
        $this->failureReason = null;
        $this->targetWorkspaceId = null;
    }

    public function canUpdateSharedRelations(): bool
    {
        return auth()->user()?->can(InstallBackupPermissionsAction::PERMISSION_PAGE_IMPORT_UPDATE_SHARED) ?? false;
    }

    /**
     * Gate hook for the downstream workspace "publish live" step that
     * happens after the import lands in the draft workspace. The check
     * lives here (rather than on ExecuteImportPlanJob) because the job
     * only stages rows into a workspace — promoting that workspace to
     * live is a separate editorial action, and this helper is what the
     * "auto-publish" flow (when it arrives in a later phase) must call
     * before it hands off to the workspace Publisher.
     */
    public function canPublishLive(): bool
    {
        return auth()->user()?->can(InstallBackupPermissionsAction::PERMISSION_PAGE_IMPORT_PUBLISH_LIVE) ?? false;
    }

    private function decisionData(): PageImportDecisionData
    {
        return new PageImportDecisionData(
            sessionId: $this->sessionId,
            reviewRows: $this->reviewRows,
            pageDecisions: $this->pageDecisions,
            resolveRows: $this->resolveRows,
            relationDecisions: $this->relationDecisions,
            canUpdateSharedRelations: $this->canUpdateSharedRelations(),
        );
    }

    private function applyWizardState(PageImportWizardStateData $state): void
    {
        $this->step = $state->step;
        $this->sessionId = $state->sessionId;
        $this->reviewRows = $state->reviewRows;
        $this->pageDecisions = $state->pageDecisions;
        $this->resolveRows = $state->resolveRows;
        $this->relationDecisions = $state->relationDecisions;
        $this->validationSummary = $state->validationSummary;

        if ($state->confirmationExpected !== '') {
            $this->confirmationExpected = $state->confirmationExpected;
            $this->confirmation = '';
        }

        $this->sendWizardNotice($state);
    }

    private function applyStatus(PageImportStatusData $status): void
    {
        $this->step = $status->step;
        $this->sessionStatus = $status->sessionStatus;
        $this->resultSummary = $status->resultSummary;
        $this->failureReason = $status->failureReason;
        $this->targetWorkspaceId = $status->targetWorkspaceId;

        $this->sendStatusNotice($status);
    }

    private function sendWizardNotice(PageImportWizardStateData $state): void
    {
        if ($state->notice === PageImportWizardStateData::NOTICE_UNRESOLVED_REFERENCES) {
            Notification::make()
                ->warning()
                ->title(__('capell-admin::exchanger.unresolved_references'))
                ->body(__('capell-admin::exchanger.unresolved_references_body', ['count' => $state->noticeCount ?? 0]))
                ->send();
        }

        if ($state->notice === PageImportWizardStateData::NOTICE_BLOCKED_BY_WORKSPACE_CONFLICT) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.blocked_by_workspace_conflict'))
                ->body(__('capell-admin::exchanger.blocked_by_workspace_conflict_body'))
                ->send();
        }

        if ($state->notice === PageImportWizardStateData::NOTICE_BLOCKED_PENDING_DECISIONS) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.blocked_pending_decisions'))
                ->send();
        }
    }

    private function sendStatusNotice(PageImportStatusData $status): void
    {
        if ($status->notice === PageImportStatusData::NOTICE_SUMMARY_BLOCKING_ERRORS) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.summary_blocking_errors'))
                ->body($status->noticeBody ?? '')
                ->send();
        }

        if ($status->notice === PageImportStatusData::NOTICE_CONFIRMATION_MISMATCH) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.confirmation_mismatch'))
                ->send();
        }

        if ($status->notice === PageImportStatusData::NOTICE_IMPORT_QUEUED) {
            Notification::make()
                ->success()
                ->title(__('capell-admin::exchanger.import_queued'))
                ->body(__('capell-admin::exchanger.import_queued_body'))
                ->send();
        }
    }
}
