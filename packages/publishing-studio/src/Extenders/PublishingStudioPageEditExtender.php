<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Extenders;

use Capell\Admin\Contracts\Extenders\PageEditExtender;
use Capell\PublishingStudio\Filament\Resources\Pages\Actions\PublishPageAction;
use Capell\PublishingStudio\Filament\Resources\Pages\Actions\ResubmitForReviewAction;
use Capell\PublishingStudio\Filament\Resources\Pages\Actions\SaveAsDraftFormAction;
use Capell\PublishingStudio\Filament\Widgets\PageAlertsWidget;
use Capell\PublishingStudio\Livewire\PageApprovalStatus;
use Filament\Actions\Action;

class PublishingStudioPageEditExtender implements PageEditExtender
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
