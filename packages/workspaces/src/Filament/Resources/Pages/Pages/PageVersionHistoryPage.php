<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Pages\Pages;

use BackedEnum;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Models\Page;
use Capell\Workspaces\Actions\DeletePageDraftAction;
use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Services\WorkspaceDiffService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page as FilamentPage;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

/**
 * Full-page version history for a Page record. Shows all workspace draft copies
 * of this page in a timeline sidebar; selecting one renders the diff against live.
 *
 * @property Page $record
 */
class PageVersionHistoryPage extends FilamentPage
{
    use InteractsWithRecord;

    public ?int $selectedWorkspaceId = null;

    protected static string $resource = PageResource::class;

    protected static ?string $slug = '{record}/history';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'capell-workspaces::filament.resources.pages.version-history';

    /** @return class-string<PageResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Page);
    }

    public function mount(string|int $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeResourceAccess();

        $first = $this->getWorkspaceCopies()->first();

        if ($first instanceof Page) {
            $this->selectedWorkspaceId = $first->workspace_id;
        }
    }

    public function getTitle(): string|Htmlable
    {
        return __('capell-admin::button.version_history') . ' — ' . $this->record->name;
    }

    public function selectVersion(int $workspaceId): void
    {
        $this->selectedWorkspaceId = $workspaceId;
    }

    public function deleteVersion(int $draftId): void
    {
        $draft = Page::query()->withoutGlobalScopes()->findOrFail($draftId);

        $this->authorize('update', $draft);

        $workspaceName = $draft->workspace?->name ?? '—';

        if ($this->selectedWorkspaceId === $draft->workspace_id) {
            $this->selectedWorkspaceId = null;
        }

        DeletePageDraftAction::run($draft);

        Notification::make()
            ->title(__('capell-admin::message.draft_deleted_notification', ['workspace' => $workspaceName]))
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, Page>
     */
    public function getWorkspaceCopies(): Collection
    {
        return Page::query()
            ->withoutGlobalScopes()
            ->where('uuid', $this->record->uuid)
            ->where('workspace_id', '!=', 0)
            ->with(['workspace' => fn ($query) => $query->with('creator'), 'pageUrl'])
            ->latest('updated_at')
            ->get();
    }

    public function getSelectedWorkspace(): ?Workspace
    {
        if ($this->selectedWorkspaceId === null) {
            return null;
        }

        return Workspace::query()->find($this->selectedWorkspaceId);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getDiffs(): Collection
    {
        $workspace = $this->getSelectedWorkspace();

        if (! $workspace instanceof Workspace) {
            return collect();
        }

        return (new WorkspaceDiffService)->diff($workspace);
    }

    public function renderHtmlDiff(mixed $before, mixed $after): string
    {
        return (new WorkspaceDiffService)->renderHtmlDiff($before, $after);
    }

    public function isLongText(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return str_contains($value, "\n") || strlen($value) > 120;
    }

    public function getWorkspaceUrl(Workspace $workspace): string
    {
        return WorkspaceResource::getUrl('compare', ['record' => $workspace]);
    }

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('capell-admin::button.edit_page'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('edit', ['record' => $this->record])),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'copies' => $this->getWorkspaceCopies(),
            'diffs' => $this->getDiffs(),
            'selectedWorkspace' => $this->getSelectedWorkspace(),
        ];
    }
}
