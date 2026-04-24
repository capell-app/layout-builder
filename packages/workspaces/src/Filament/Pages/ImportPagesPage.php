<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Admin\Actions\InstallExchangerPermissionsAction;
use Capell\Admin\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\Core\Exchanger\Actions\BuildImportValidationSummaryAction;
use Capell\Core\Exchanger\Actions\BuildPageReviewRows;
use Capell\Core\Exchanger\Actions\BuildRelationResolveRowsAction;
use Capell\Core\Exchanger\Data\PageReviewRow;
use Capell\Core\Exchanger\Data\RelationResolveRow;
use Capell\Core\Exchanger\Enums\ImportSessionKind;
use Capell\Core\Exchanger\Enums\ImportSessionStatus;
use Capell\Core\Exchanger\Jobs\ExecuteImportPlanJob;
use Capell\Core\Exchanger\Models\ImportSession;
use Capell\Core\Exchanger\Services\Import\ManifestValidator;
use Capell\Core\Exchanger\Services\Import\PackageReader;
use Capell\Core\Exchanger\Services\Import\ResolutionMap;
use Capell\Core\Exchanger\Services\Import\ResolutionMapBuilder;
use Capell\Core\Exchanger\Services\Import\Resolvers\MatchResolution;
use Capell\Core\Exchanger\Services\Import\Resolvers\RelationMatchResolverRegistry;
use Capell\Core\Models\Site;
use Capell\Workspaces\Enums\WorkspaceKindEnum;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
use Capell\Workspaces\Models\Workspace;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Override;
use RuntimeException;
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

    public function form(Schema $schema): Schema
    {
        return $schema
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

        if ($this->hasBlockingWorkspaceConflict()) {
            return;
        }

        $this->advanceToResolve();
    }

    public function parseAndAdvance(): void
    {
        $state = $this->data;

        $archiveDiskPath = is_array($state['archive'] ?? null)
            ? (string) array_values($state['archive'])[0]
            : (string) ($state['archive'] ?? '');

        if ($archiveDiskPath === '') {
            Notification::make()->danger()->title(__('capell-admin::exchanger.upload_required'))->send();

            return;
        }

        $absolutePath = Storage::disk('local')->path($archiveDiskPath);

        $sourceFilename = is_array($state['archive_filename'] ?? null)
            ? (string) array_values($state['archive_filename'])[0]
            : null;

        $workspaceName = (string) ($state['workspace_name'] ?? '');
        if ($workspaceName === '') {
            $workspaceName = sprintf(
                '%s — %s',
                __('capell-admin::exchanger.import_workspace_default_name'),
                now()->format('Y-m-d H:i'),
            );
        }

        $workspace = Workspace::query()->create([
            'name' => $workspaceName,
            'status' => WorkspaceStatusEnum::Open->value,
            'kind' => WorkspaceKindEnum::Import->value,
        ]);

        $session = ImportSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => auth()->id(),
            'kind' => ImportSessionKind::PageImport,
            'status' => ImportSessionStatus::Draft,
            'source_filename' => $sourceFilename,
            'source_package_path' => $archiveDiskPath,
        ]);

        // workspace_id is added by the workspaces migration but not in core's $fillable;
        // use setAttribute to bypass mass-assignment guard.
        $session->setAttribute('workspace_id', $workspace->getKey());
        $session->save();

        try {
            $package = (new PackageReader)->read($absolutePath);

            $validation = (new ManifestValidator)->validate($package->manifest);
            if (! $validation->isValid()) {
                throw new RuntimeException(implode(' / ', $validation->errors));
            }

            $session->forceFill([
                'manifest' => $package->manifest,
                'status' => ImportSessionStatus::Parsed,
            ])->save();

            $resolutionMap = (new ResolutionMapBuilder(
                resolve(RelationMatchResolverRegistry::class),
            ))->build($package->payload);

            $session->forceFill([
                'resolution_map' => $resolutionMap->toArray(),
                'status' => $resolutionMap->hasUnresolved() ? ImportSessionStatus::Mapped : ImportSessionStatus::Parsed,
            ])->save();

            if ($resolutionMap->hasUnresolved()) {
                Notification::make()
                    ->warning()
                    ->title(__('capell-admin::exchanger.unresolved_references'))
                    ->body(__('capell-admin::exchanger.unresolved_references_body', ['count' => count($resolutionMap->unresolved)]))
                    ->send();
            }

            $reviewRows = (new BuildPageReviewRows)->run(
                $package,
                $resolutionMap,
                excludeWorkspaceId: (int) $workspace->getKey(),
            );

            $this->reviewRows = array_map(
                static fn (PageReviewRow $row): array => $row->toArray(),
                $reviewRows,
            );

            $this->pageDecisions = [];
            foreach ($reviewRows as $row) {
                $this->pageDecisions[$row->uuid] = ['action' => $row->suggestedAction];
            }

            $resolveRows = BuildRelationResolveRowsAction::run($resolutionMap);
            $this->resolveRows = array_map(
                static fn (RelationResolveRow $row): array => $row->toArray(),
                $resolveRows,
            );
            $this->relationDecisions = [];
            foreach ($resolveRows as $row) {
                $targetId = $row->topMatch['local_id'] ?? null;
                $this->relationDecisions[$row->ref] = [
                    'action' => $row->suggestedAction,
                    'target_id' => $targetId,
                ];
            }

            $this->sessionId = (int) $session->getKey();
            $this->step = self::STEP_REVIEW;
        } catch (Throwable $throwable) {
            $session->forceFill([
                'status' => ImportSessionStatus::Failed,
                'failure_reason' => $throwable->getMessage(),
            ])->save();

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

        if ($this->hasBlockingWorkspaceConflict()) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.blocked_by_workspace_conflict'))
                ->body(__('capell-admin::exchanger.blocked_by_workspace_conflict_body'))
                ->send();

            return;
        }

        if ($this->shouldSkipResolveStep()) {
            $this->advanceToValidate();

            return;
        }

        $this->step = self::STEP_RESOLVE;
    }

    public function backToReview(): void
    {
        $this->step = self::STEP_REVIEW;
    }

    public function backToResolve(): void
    {
        if ($this->shouldSkipResolveStep()) {
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

        if ($this->hasBlockingWorkspaceConflict()) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.blocked_by_workspace_conflict'))
                ->body(__('capell-admin::exchanger.blocked_by_workspace_conflict_body'))
                ->send();

            return;
        }

        if (! $this->shouldSkipResolveStep() && ! $this->hasValidRelationDecisions()) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.blocked_pending_decisions'))
                ->send();

            $this->step = self::STEP_RESOLVE;

            return;
        }

        $session = ImportSession::query()->find($this->sessionId);
        if (! $session instanceof ImportSession) {
            return;
        }

        try {
            $archiveAbsolutePath = Storage::disk('local')->path((string) $session->source_package_path);
            $package = (new PackageReader)->read($archiveAbsolutePath);
        } catch (Throwable $throwable) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.import_failed'))
                ->body($throwable->getMessage())
                ->send();

            return;
        }

        $resolutionMap = $this->hydrateResolutionMap(is_array($session->resolution_map) ? $session->resolution_map : []);

        $workspace = Workspace::query()->find($session->workspace_id);
        $excludeWorkspaceId = $workspace instanceof Workspace ? (int) $workspace->getKey() : null;

        $summary = (new BuildImportValidationSummaryAction)->run(
            package: $package,
            map: $resolutionMap,
            pageDecisions: $this->sanitizedPageDecisions(),
            relationDecisions: $this->sanitizedRelationDecisions(),
            workspace: $workspace instanceof Workspace ? $workspace : null,
            excludeWorkspaceId: $excludeWorkspaceId,
        );

        $session->forceFill([
            'page_decisions' => $this->sanitizedPageDecisions(),
            'relation_decisions' => $this->sanitizedRelationDecisions(),
            'validation_results' => $summary->toArray(),
            'status' => ImportSessionStatus::Validated,
        ])->save();

        $this->validationSummary = $summary->toArray();
        $this->confirmationExpected = $this->deriveConfirmationTarget($resolutionMap, $workspace instanceof Workspace ? $workspace : null);
        $this->confirmation = '';
        $this->step = self::STEP_VALIDATE;
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

        $blockingErrors = $this->validationSummary['blocking_errors'] ?? [];
        if (is_array($blockingErrors) && $blockingErrors !== []) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.summary_blocking_errors'))
                ->body(implode(' / ', array_filter($blockingErrors, is_string(...))))
                ->send();

            return;
        }

        if (! $this->confirmationMatches()) {
            Notification::make()
                ->danger()
                ->title(__('capell-admin::exchanger.confirmation_mismatch'))
                ->send();

            return;
        }

        $session = ImportSession::query()->find($this->sessionId);
        if (! $session instanceof ImportSession) {
            return;
        }

        $session->forceFill([
            'status' => ImportSessionStatus::Queued,
        ])->save();

        dispatch(new ExecuteImportPlanJob((int) $session->getKey()));

        $this->sessionStatus = ImportSessionStatus::Queued->value;
        $this->resultSummary = [];
        $this->failureReason = null;
        $this->targetWorkspaceId = $session->workspace_id;
        $this->step = self::STEP_EXECUTING;

        Notification::make()
            ->success()
            ->title(__('capell-admin::exchanger.import_queued'))
            ->body(__('capell-admin::exchanger.import_queued_body'))
            ->send();
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

        $session = ImportSession::query()->find($this->sessionId);
        if (! $session instanceof ImportSession) {
            return;
        }

        $status = $session->status;
        $this->sessionStatus = $status->value;
        $this->resultSummary = is_array($session->result_summary) ? $session->result_summary : [];
        $this->targetWorkspaceId = $session->workspace_id ?? $this->targetWorkspaceId;

        if ($status === ImportSessionStatus::Completed) {
            $this->step = self::STEP_COMPLETED;

            return;
        }

        if ($status === ImportSessionStatus::Failed) {
            $this->failureReason = $session->failure_reason;
            $this->step = self::STEP_FAILED;
        }
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
        return auth()->user()?->can(InstallExchangerPermissionsAction::PERMISSION_PAGE_IMPORT_UPDATE_SHARED) ?? false;
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
        return auth()->user()?->can(InstallExchangerPermissionsAction::PERMISSION_PAGE_IMPORT_PUBLISH_LIVE) ?? false;
    }

    private function shouldSkipResolveStep(): bool
    {
        if ($this->resolveRows === []) {
            return true;
        }

        foreach ($this->resolveRows as $row) {
            if (($row['top_match'] ?? null) === null) {
                return false;
            }

            $alternatives = $row['alternatives'] ?? [];
            if (is_array($alternatives) && $alternatives !== []) {
                return false;
            }
        }

        return true;
    }

    private function hasValidRelationDecisions(): bool
    {
        foreach ($this->resolveRows as $row) {
            $ref = (string) ($row['ref'] ?? '');
            $decision = $this->relationDecisions[$ref] ?? null;
            if (! is_array($decision)) {
                return false;
            }

            $action = $decision['action'] ?? '';

            switch ($action) {
                case RelationResolveRow::ACTION_USE_EXISTING:
                    $targetId = $decision['target_id'] ?? null;
                    if ($targetId === null || $targetId === '') {
                        return false;
                    }

                    break;
                case RelationResolveRow::ACTION_UPDATE_EXISTING:
                    if (! $this->canUpdateSharedRelations()) {
                        return false;
                    }

                    $targetId = $decision['target_id'] ?? null;
                    if ($targetId === null || $targetId === '') {
                        return false;
                    }

                    break;
                case RelationResolveRow::ACTION_CREATE_NEW:
                case RelationResolveRow::ACTION_CLONE_IMPORTED:
                case RelationResolveRow::ACTION_SKIP:
                    break;
                default:
                    return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array{action: string, notes?: string}>
     */
    private function sanitizedPageDecisions(): array
    {
        $sanitized = [];
        foreach ($this->pageDecisions as $uuid => $decision) {
            if (! is_string($uuid)) {
                continue;
            }

            if (! is_array($decision)) {
                continue;
            }

            $action = is_string($decision['action'] ?? null) ? $decision['action'] : PageReviewRow::ACTION_CREATE;
            $entry = ['action' => $action];

            if (isset($decision['notes']) && is_string($decision['notes']) && $decision['notes'] !== '') {
                $entry['notes'] = $decision['notes'];
            }

            $sanitized[$uuid] = $entry;
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>  $persisted
     */
    private function hydrateResolutionMap(array $persisted): ResolutionMap
    {
        $resolvedSource = is_array($persisted['resolved'] ?? null) ? $persisted['resolved'] : [];
        $unresolvedSource = is_array($persisted['unresolved'] ?? null) ? $persisted['unresolved'] : [];

        $resolved = [];
        foreach ($resolvedSource as $ref => $entry) {
            if (! is_string($ref)) {
                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $resolved[$ref] = $this->matchResolutionFrom($entry);
        }

        $unresolved = array_values(array_filter(
            $unresolvedSource,
            is_string(...),
        ));

        return new ResolutionMap($resolved, $unresolved);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function matchResolutionFrom(array $entry): MatchResolution
    {
        $localId = $entry['local_id'] ?? 0;
        if (! is_int($localId) && ! is_string($localId)) {
            $localId = 0;
        }

        $alternatives = [];
        $alternativesSource = $entry['alternatives'] ?? [];
        if (is_array($alternativesSource)) {
            foreach ($alternativesSource as $alternative) {
                if (is_array($alternative)) {
                    $alternatives[] = $this->matchResolutionFrom($alternative);
                }
            }
        }

        return new MatchResolution(
            localId: $localId,
            strategy: is_string($entry['strategy'] ?? null) ? $entry['strategy'] : '',
            confidence: is_numeric($entry['confidence'] ?? null) ? (float) $entry['confidence'] : 1.0,
            reason: is_string($entry['reason'] ?? null) ? $entry['reason'] : '',
            alternatives: $alternatives,
        );
    }

    private function deriveConfirmationTarget(ResolutionMap $map, ?Workspace $workspace): string
    {
        $siteIds = [];
        foreach ($map->resolved as $ref => $resolution) {
            if (! str_starts_with($ref, 'site:')) {
                continue;
            }

            $localId = $resolution->localId;
            if (is_int($localId)) {
                $siteIds[$localId] = true;
            } elseif (is_string($localId) && ctype_digit($localId)) {
                $siteIds[(int) $localId] = true;
            }
        }

        if (count($siteIds) === 1) {
            $siteId = array_key_first($siteIds);
            $site = Site::query()->find($siteId);
            if ($site instanceof Site && is_string($site->name) && $site->name !== '') {
                return $site->name;
            }
        }

        if ($workspace instanceof Workspace && is_string($workspace->name) && $workspace->name !== '') {
            return $workspace->name;
        }

        return '';
    }

    /**
     * @return array<string, array{action: string, target_id?: int|string, notes?: string}>
     */
    private function sanitizedRelationDecisions(): array
    {
        $sanitized = [];
        foreach ($this->relationDecisions as $ref => $decision) {
            if (! is_string($ref)) {
                continue;
            }

            if (! is_array($decision)) {
                continue;
            }

            $action = $decision['action'] ?? RelationResolveRow::ACTION_USE_EXISTING;
            $entry = ['action' => $action];

            $targetId = $decision['target_id'] ?? null;
            if (is_int($targetId) || (is_string($targetId) && $targetId !== '')) {
                $entry['target_id'] = $targetId;
            }

            if (isset($decision['notes']) && is_string($decision['notes']) && $decision['notes'] !== '') {
                $entry['notes'] = $decision['notes'];
            }

            $sanitized[$ref] = $entry;
        }

        return $sanitized;
    }

    private function hasBlockingWorkspaceConflict(): bool
    {
        foreach ($this->reviewRows as $row) {
            if (($row['collision_state'] ?? null) !== PageReviewRow::COLLISION_URL_WORKSPACE) {
                continue;
            }

            $uuid = (string) ($row['uuid'] ?? '');
            $action = $this->pageDecisions[$uuid]['action'] ?? PageReviewRow::ACTION_SKIP;
            if ($action !== PageReviewRow::ACTION_SKIP) {
                return true;
            }
        }

        return false;
    }
}
