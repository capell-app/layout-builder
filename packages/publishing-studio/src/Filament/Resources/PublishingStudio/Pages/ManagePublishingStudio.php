<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio\Pages;

use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\PublishingStudio\Enums\ResourceEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Filament\Widgets\WorkspaceMergeHistoryWidgetAbstract as WorkspaceMergeHistoryWidget;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ManagePublishingStudio extends ManageRecords
{
    /** @return class-string<WorkspaceResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Workspace);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-admin::hints.publishing-studio');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('capell-admin::generic.all')),
            'open' => Tab::make(WorkspaceStatusEnum::Open->getLabel())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', WorkspaceStatusEnum::Open->value),
                ),
            'in_review' => Tab::make(WorkspaceStatusEnum::InReview->getLabel())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', WorkspaceStatusEnum::InReview->value),
                ),
            'approved' => Tab::make(WorkspaceStatusEnum::Approved->getLabel())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', WorkspaceStatusEnum::Approved->value),
                ),
            'scheduled' => Tab::make(WorkspaceStatusEnum::Scheduled->getLabel())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', WorkspaceStatusEnum::Scheduled->value),
                ),
        ];
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            WorkspaceMergeHistoryWidget::class,
        ];
    }
}
