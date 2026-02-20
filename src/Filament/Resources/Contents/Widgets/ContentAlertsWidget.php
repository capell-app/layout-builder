<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\Widgets;

use Capell\Admin\Data\MessageData;
use Capell\Admin\Enums\AlertTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\HasBlankPlaceholder;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Layout\Enums\ResourceEnum;
use Capell\Layout\Filament\Actions\DeleteDraftContentAction;
use Capell\Layout\Filament\Actions\PublishContentAction;
use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Filament\Resources\Contents\Pages\EditContent;
use Capell\Layout\Models\Content;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

#[On('refresh-alerts')]
class ContentAlertsWidget extends Widget implements HasActions, HasForms
{
    use HasBlankPlaceholder;
    use InteractsWithActions;
    use InteractsWithForms;

    public ?Content $record = null;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'capell-admin::components.widgets.alerts';

    public function mount(): void
    {
        $this->loadRecord();
    }

    public function hydrate(): void
    {
        $this->loadRecord();
    }

    public function publishAction(): Action
    {
        return PublishContentAction::make()
            ->record($this->record)
            ->after(function (): void {
                $this->dispatch('$refresh')->to(EditContent::class);
            });
    }

    public function deleteDraftAction(): Action
    {
        return DeleteDraftContentAction::make()
            ->record($this->record);
    }

    public function viewCurrentContentAction(): Action
    {
        return Action::make('viewCurrent')
            ->label(__('capell-admin::button.current_draft'))
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->livewireClickHandlerEnabled(false)
            ->link()
            ->url(static::getResource()::getUrl('edit', ['record' => $this->record->getCurrent()->id]));
    }

    /**
     * @return Collection<string, MessageData>
     */
    #[Computed]
    public function alerts(): Collection
    {
        $alerts = collect();

        if ($this->record->draft) {
            if ($this->record->isCurrent()) {
                $alerts->put('draft', new MessageData(
                    message: __(
                        'capell-admin::message.draft_resource',
                        ['name' => __('capell-layout::generic.content')],
                    ),
                    type: AlertTypeEnum::Info,
                    icon: 'heroicon-o-shield-exclamation',
                    action: [
                        $this->deleteDraftAction(),
                        $this->publishAction(),
                    ],
                ));
            } else {
                $alerts->put('draft', new MessageData(
                    message: __(
                        'capell-admin::message.draft_stale_resource',
                        ['name' => __('capell-layout::generic.content')],
                    ),
                    type: AlertTypeEnum::Warning,
                    icon: 'heroicon-o-shield-exclamation',
                    action: $this->viewCurrentContentAction(),
                ));
            }
        }

        if ($this->record->trashed()) {
            $alerts->put('deleted', new MessageData(
                message: __(
                    'capell-admin::message.resource_deleted',
                    ['name' => __('capell-layout::generic.content')],
                ),
                type: AlertTypeEnum::Warning,
                icon: 'heroicon-m-exclamation-triangle',
            ));
        }

        switch ($this->record->publish_status) {
            case PublishStatusEnum::pending:
                $alerts->put('pending', new MessageData(
                    message: __('capell-admin::message.resource_pending', [
                        'date' => $this->record->publish_from?->diffForHumans(),
                        'name' => __('capell-layout::generic.content'),
                    ]),
                    type: AlertTypeEnum::Warning,
                    icon: 'heroicon-o-clock',
                ));
                break;
            case PublishStatusEnum::expired:
                $alerts->put('expired', new MessageData(
                    message: __('capell-admin::message.resource_expired', [
                        'date' => $this->record->publish_to?->diffForHumans(),
                        'name' => __('capell-layout::generic.content'),
                    ]),
                    type: AlertTypeEnum::Warning,
                    icon: 'heroicon-o-clock',
                ));
                break;
        }

        return $alerts;
    }

    protected function loadRecord(): void
    {
        $this->record->load([
            'site' => fn ($query) => $query->withTrashed(),
        ]);
    }

    /**
     * @return class-string<ContentResource>
     */
    private function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Content->value);
    }
}
