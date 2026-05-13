<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\PublishingStudio\Actions\Workflow\BuildPublishingWorkflowCommandCenterAction;
use Capell\PublishingStudio\Data\Workflow\PublishingWorkflowPanelData;
use Capell\PublishingStudio\Support\WorkspaceSchema;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class PublishingWorkflowPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::DocumentCheck;

    protected static ?string $slug = 'publishing-workflow';

    protected static ?int $navigationSort = 0;

    protected string $view = 'capell-publishing-studio::filament.pages.publishing-workflow';

    #[Override]
    public static function canAccess(): bool
    {
        return WorkspaceSchema::isReady() && parent::canAccess();
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-publishing-studio::workflow.navigation.label');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_workflow');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-publishing-studio::workflow.subheading');
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-publishing-studio::workflow.title');
    }

    /**
     * @return list<PublishingWorkflowPanelData>
     */
    public function panels(): array
    {
        return BuildPublishingWorkflowCommandCenterAction::run(auth()->user());
    }
}
