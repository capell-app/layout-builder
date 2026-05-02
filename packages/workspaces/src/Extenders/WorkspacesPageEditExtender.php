<?php

declare(strict_types=1);

namespace Capell\Workspaces\Extenders;

use Capell\Admin\Contracts\Extenders\PageEditExtender;
use Capell\Workspaces\Filament\Resources\Pages\Actions\PublishPageAction;
use Capell\Workspaces\Filament\Resources\Pages\Actions\ResubmitForReviewAction;
use Capell\Workspaces\Filament\Resources\Pages\Actions\SaveAsDraftFormAction;
use Capell\Workspaces\Filament\Widgets\PageAlertsWidget;
use Capell\Workspaces\Livewire\PageApprovalStatus;
use Filament\Actions\Action;

class WorkspacesPageEditExtender implements PageEditExtender
{
    /** @return array<int, Action> */
    public function getFormActions(): array
    {
        return [
            SaveAsDraftFormAction::make(),
            PublishPageAction::make(),
            ResubmitForReviewAction::make(),
        ];
    }

    /** @return array<int, mixed> */
    public function getHeaderWidgets(): array
    {
        return [
            PageAlertsWidget::class,
            PageApprovalStatus::class,
        ];
    }
}
