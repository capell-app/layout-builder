<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Pages;

use BackedEnum;
use Capell\Workspaces\Checks\PublishCheckPipeline;
use Capell\Workspaces\Checks\PublishCheckResult;
use Capell\Workspaces\Checks\PublishCheckSeverity;
use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Services\WorkspaceDiffService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Side-by-side comparison of every workspace-scoped record against live.
 * Reuses {@see WorkspaceDiffService::diff()} for attribute pairs and
 * {@see WorkspaceDiffService::renderHtmlDiff()} (powered by jfcherng/php-diff)
 * for any diff whose before/after both look like multi-line or long text.
 */
class CompareVersionPage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = WorkspaceResource::class;

    protected static ?string $slug = '{record}/compare';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'capell-workspaces::components.pages.workspaces.compare';

    public function mount(string|int $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeResourceAccess();
    }

    public function getTitle(): string|Htmlable
    {
        return __('capell-admin::workspace.compare.title', ['workspace' => $this->getWorkspace()->name]);
    }

    public function getWorkspace(): Workspace
    {
        $workspace = $this->getRecord();

        throw_unless($workspace instanceof Workspace, RuntimeException::class, 'Compare page resolved a non-Workspace record.');

        return $workspace;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getDiffs(): Collection
    {
        return (new WorkspaceDiffService)->diffTree($this->getWorkspace());
    }

    public function renderHtmlDiff(mixed $before, mixed $after): string
    {
        return (new WorkspaceDiffService)->renderHtmlDiff($before, $after);
    }

    /**
     * Long / multi-line strings get rendered through the HTML differ;
     * everything else is shown as plain before/after cells. Keeps the
     * side-by-side table lightweight when values are short.
     */
    public function isLongText(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return str_contains($value, "\n") || strlen($value) > 120;
    }

    /**
     * @return array<int, PublishCheckResult>
     */
    public function getCheckResults(): array
    {
        return resolve(PublishCheckPipeline::class)->run($this->getWorkspace());
    }

    public function checkSeverityColor(PublishCheckSeverity $severity): string
    {
        return match ($severity) {
            PublishCheckSeverity::Error => 'danger',
            PublishCheckSeverity::Warn => 'warning',
            PublishCheckSeverity::Info => 'info',
        };
    }

    protected function getViewData(): array
    {
        return [
            'diffs' => $this->getDiffs(),
            'workspace' => $this->getWorkspace(),
            'checkResults' => $this->getCheckResults(),
        ];
    }
}
