<?php

declare(strict_types=1);

namespace Capell\Workspaces\Providers;

use Capell\Admin\Contracts\Extenders\PageEditExtender;
use Capell\Admin\Contracts\Extenders\PageExportExtender;
use Capell\Admin\Contracts\Extenders\PageResourcePageExtender;
use Capell\Admin\Contracts\Extenders\PageTableExtender;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Workspaces\Actions\CopyOnWriteAction;
use Capell\Workspaces\BelongsToWorkspace;
use Capell\Workspaces\Events\WorkspaceEventDispatcher;
use Capell\Workspaces\Extenders\WorkspacesPageEditExtender;
use Capell\Workspaces\Extenders\WorkspacesPageExportExtender;
use Capell\Workspaces\Extenders\WorkspacesPageResourcePageExtender;
use Capell\Workspaces\Extenders\WorkspacesPageTableExtender;
use Capell\Workspaces\Http\Livewire\WorkspacePageDraftHandler;
use Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext;
use Capell\Workspaces\Listeners\StampWorkspaceOnActivity;
use Capell\Workspaces\Models\PreviewLink;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceApproval;
use Capell\Workspaces\Models\WorkspaceFieldComment;
use Capell\Workspaces\Models\WorkspaceReviewAssignment;
use Capell\Workspaces\Support\WorkspacesManager;
use Capell\Workspaces\WorkspaceContext;
use Capell\Workspaces\WorkspaceContextScope;
use Capell\Workspaces\WorkspaceRegistry;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Contracts\Routing\Registrar as Router;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class WorkspacesServiceProvider extends ServiceProvider
{
    /** @var array<class-string<Model>, true> */
    private array $workspaceBehaviorApplied = [];

    public function register(): void
    {
        $this->app->register(AdminServiceProvider::class);

        CapellCore::registerPackage('capell-app/workspaces', path: realpath(__DIR__ . '/../..'));
        CapellCore::registerModels([
            PreviewLink::class,
            Version::class,
            Workspace::class,
            WorkspaceApproval::class,
            WorkspaceFieldComment::class,
            WorkspaceReviewAssignment::class,
        ]);

        $this->app->singleton(WorkspacesManager::class, fn (): WorkspacesManager => new WorkspacesManager);
        $this->app->singleton(WorkspaceEventDispatcher::class);
        $this->app->singleton('capell.workspace.page-draft-handler', WorkspacePageDraftHandler::class);
        $this->app->tag([WorkspacesPageTableExtender::class], PageTableExtender::TAG);
        $this->app->tag([WorkspacesPageEditExtender::class], PageEditExtender::TAG);
        $this->app->tag([WorkspacesPageExportExtender::class], PageExportExtender::TAG);
        $this->app->tag([WorkspacesPageResourcePageExtender::class], PageResourcePageExtender::TAG);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-workspaces');

        $this->registerMorphMap();

        if (! CapellCore::isPackageInstalled('capell-app/workspaces')) {
            return;
        }

        $this->registerWorkspaceDraftables()
            ->applyBehaviorToDraftableModels()
            ->registerBuilderMacros()
            ->registerMiddleware()
            ->registerEventListeners()
            ->registerFrontendRenderHooks();

        $this->app->booted(function (): void {
            $this
                ->registerPageTypeDraftables()
                ->applyBehaviorToDraftableModels();
        });
    }

    private function registerMorphMap(): self
    {
        Relation::morphMap([
            'workspace' => Workspace::class,
            'workspace_approval' => WorkspaceApproval::class,
            'workspace_field_comment' => WorkspaceFieldComment::class,
            'workspace_review_assignment' => WorkspaceReviewAssignment::class,
            'version' => Version::class,
            'preview_link' => PreviewLink::class,
        ]);

        return $this;
    }

    private function registerWorkspaceDraftables(): self
    {
        $simpleModels = [
            Site::class,
            SiteDomain::class,
            Type::class,
            Theme::class,
            Layout::class,
            Language::class,
            Media::class,
            PageUrl::class,
            AssetRelation::class,
        ];

        foreach ($simpleModels as $modelClass) {
            $this->registerDraftableModel($modelClass);
        }

        // Page requires a finalizeOnPublish hook to retarget PageUrl + Translation rows.
        WorkspaceRegistry::register(Page::class, finalizeOnPublish: static function (Page $draftRow): Page {
            if ($draftRow->uuid === null || $draftRow->uuid === '' || (int) $draftRow->workspace_id === 0) {
                return $draftRow;
            }

            $workspaceId = $draftRow->workspace_id;
            $draftPageId = (int) $draftRow->getKey();
            $morphClass = $draftRow->getMorphClass();

            $oldLiveId = Page::query()
                ->withoutGlobalScopes()
                ->where('uuid', $draftRow->uuid)
                ->where('workspace_id', 0)
                ->value('id');

            if ($oldLiveId === null) {
                return $draftRow;
            }

            PageUrl::query()
                ->withoutGlobalScopes()
                ->where('pageable_type', $morphClass)
                ->where('pageable_id', $oldLiveId)
                ->where('workspace_id', 0)
                ->update(['pageable_id' => $draftPageId]);

            // CoW-cloned translations have translatable_id = oldLiveId (the live page id),
            // because they were cloned before the page itself was copied. Retarget them to
            // draftPageId so they point to the correct page after the workspace_id flip.
            $coveredLanguageIds = Translation::query()
                ->withoutGlobalScopes()
                ->where('translatable_type', $morphClass)
                ->where('translatable_id', $oldLiveId)
                ->where('workspace_id', $workspaceId)
                ->pluck('language_id')
                ->all();

            if ($coveredLanguageIds !== []) {
                Translation::query()
                    ->withoutGlobalScopes()
                    ->where('translatable_type', $morphClass)
                    ->where('translatable_id', $oldLiveId)
                    ->where('workspace_id', $workspaceId)
                    ->update(['translatable_id' => $draftPageId]);

                // Delete live translations that are superseded by workspace ones so the
                // unique constraint is not violated when workspace translations are flipped
                // to workspace_id = 0.
                Translation::query()
                    ->withoutGlobalScopes()
                    ->where('translatable_type', $morphClass)
                    ->where('translatable_id', $oldLiveId)
                    ->where('workspace_id', 0)
                    ->whereIn('language_id', $coveredLanguageIds)
                    ->delete();
            }

            // Retarget uncovered live translations to the new page id.
            Translation::query()
                ->withoutGlobalScopes()
                ->where('translatable_type', $morphClass)
                ->where('translatable_id', $oldLiveId)
                ->where('workspace_id', 0)
                ->when(
                    $coveredLanguageIds !== [],
                    static fn (Builder $query): Builder => $query->whereNotIn('language_id', $coveredLanguageIds),
                )
                ->update(['translatable_id' => $draftPageId]);

            return $draftRow;
        });

        // Translation is registered AFTER Page so that Publisher processes Page first
        // during publish. Page's finalizeOnPublish retargets and deletes conflicting
        // live translations before Translation rows are flipped to workspace_id = 0.
        $this->registerDraftableModel(Translation::class);

        return $this;
    }

    private function registerPageTypeDraftables(): self
    {
        CapellCore::getPageTypes()
            ->pluck('model')
            ->filter(fn (mixed $modelClass): bool => is_string($modelClass) && class_exists($modelClass))
            ->each(function (string $modelClass): void {
                /** @var class-string<Model> $modelClass */
                $this->registerDraftableModel($modelClass);
            });

        return $this;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function registerDraftableModel(string $modelClass): void
    {
        if (WorkspaceRegistry::isRegistered($modelClass)) {
            return;
        }

        WorkspaceRegistry::register($modelClass);
    }

    private function applyBehaviorToDraftableModels(): self
    {
        foreach (WorkspaceRegistry::modelClasses() as $modelClass) {
            if (isset($this->workspaceBehaviorApplied[$modelClass])) {
                continue;
            }

            $this->workspaceBehaviorApplied[$modelClass] = true;

            if (in_array(BelongsToWorkspace::class, class_uses_recursive($modelClass), true)) {
                continue;
            }

            $modelClass::addGlobalScope(new WorkspaceContextScope);

            $modelClass::creating(static function (Model $record): void {
                $activeWorkspaceId = WorkspaceContext::currentId();

                if ($activeWorkspaceId === null) {
                    return;
                }

                $currentWorkspaceId = $record->getAttribute('workspace_id');
                if ($currentWorkspaceId === null || (int) $currentWorkspaceId === 0) {
                    $record->setAttribute('workspace_id', $activeWorkspaceId);
                }
            });

            $modelClass::saving(static function (Model $record): ?bool {
                $activeWorkspace = WorkspaceContext::current();

                if (! $activeWorkspace instanceof Workspace) {
                    return null;
                }

                if (! $record->exists) {
                    return null;
                }

                if ((int) $record->getAttribute('workspace_id') !== 0) {
                    return null;
                }

                if (! $record->isDirty()) {
                    return null;
                }

                (new CopyOnWriteAction)->cloneForEdit($record, $activeWorkspace);

                return false;
            });

            $modelClass::deleting(static function (Model $record): ?bool {
                $activeWorkspace = WorkspaceContext::current();

                if (! $activeWorkspace instanceof Workspace) {
                    return null;
                }

                if (! $record->exists) {
                    return null;
                }

                if ((int) $record->getAttribute('workspace_id') !== 0) {
                    return null;
                }

                (new CopyOnWriteAction)->cloneForDelete($record, $activeWorkspace);

                return false;
            });

            $modelClass::resolveRelationUsing('workspace', static fn (Model $model): BelongsTo => $model->belongsTo(Workspace::class, 'workspace_id'));
            $modelClass::resolveRelationUsing('isLive', static fn (Model $model): bool => (int) $model->getAttribute('workspace_id') === 0);
            $modelClass::resolveRelationUsing('isInWorkspace', static fn (Model $model): bool => (int) $model->getAttribute('workspace_id') > 0);
        }

        return $this;
    }

    private function registerBuilderMacros(): self
    {
        Builder::macro('live', function (): Builder {
            /** @var Builder $this */
            return $this->where($this->getModel()->qualifyColumn('workspace_id'), 0);
        });

        Builder::macro('inWorkspace', function (Workspace|int $workspace): Builder {
            /** @var Builder $this */
            $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

            return $this->where($this->getModel()->qualifyColumn('workspace_id'), $workspaceId);
        });

        Builder::macro('forContext', function (Workspace|int|null $workspace): Builder {
            /** @var Builder $this */
            $workspaceColumn = $this->getModel()->qualifyColumn('workspace_id');

            if ($workspace === null) {
                return $this->where($workspaceColumn, 0);
            }

            $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;
            $shadowedColumn = $this->getModel()->qualifyColumn('shadowed_by_workspace_id');

            return $this->where(
                static function (Builder $inner) use ($workspaceColumn, $shadowedColumn, $workspaceId): void {
                    $inner->where($workspaceColumn, $workspaceId)
                        ->orWhere(
                            static function (Builder $liveBranch) use ($workspaceColumn, $shadowedColumn, $workspaceId): void {
                                $liveBranch->where($workspaceColumn, 0)
                                    ->where($shadowedColumn, '!=', $workspaceId);
                            },
                        );
                },
            );
        });

        Builder::macro('withoutWorkspaceScope', function (): Builder {
            /** @var Builder $this */
            return $this->withoutGlobalScope(WorkspaceContextScope::class);
        });

        return $this;
    }

    private function registerMiddleware(): self
    {
        if ($this->app->bound(HttpKernel::class) && $this->app->bound(Router::class)) {
            $this->app->make(Router::class)
                ->aliasMiddleware('workspace.context', ResolveWorkspaceContext::class);
        }

        return $this;
    }

    private function registerEventListeners(): self
    {
        $activityModel = config('activitylog.activity_model', Activity::class);

        Event::listen(
            'eloquent.creating: ' . $activityModel,
            [StampWorkspaceOnActivity::class, 'handle'],
        );

        return $this;
    }

    private function registerFrontendRenderHooks(): self
    {
        if (! $this->app->bound(RenderHookRegistry::class)) {
            return $this;
        }

        $this->app->make(RenderHookRegistry::class)->register(
            RenderHookLocation::BodyEnd,
            static fn (): string => view('capell-workspaces::components.workspace-preview-pill')->render(),
        );

        return $this;
    }
}
