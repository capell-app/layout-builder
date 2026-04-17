<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Collections\Widgets;

use Capell\Admin\Data\MessageData;
use Capell\Admin\Enums\AlertTypeEnum;
use Capell\Admin\Filament\Concerns\HasBlankPlaceholder;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Layout\Models\Collection;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

#[On('refresh-alerts')]
class ContentAlertsWidget extends Widget implements HasActions, HasForms
{
    use HasBlankPlaceholder;
    use InteractsWithActions;
    use InteractsWithForms;

    public ?Collection $record = null;

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

    /**
     * @return SupportCollection<string, MessageData>
     */
    #[Computed]
    public function alerts(): SupportCollection
    {
        $alerts = collect();

        if ($this->record->trashed()) {
            $alerts->put('trashed', new MessageData(
                message: __(
                    'capell-admin::message.resource_deleted',
                    ['name' => __('capell-layout::generic.content')],
                ),
                type: AlertTypeEnum::Warning,
                icon: 'heroicon-m-exclamation-triangle',
            ));
        }

        match ($this->record->publish_status) {
            PublishStatusEnum::pending => $alerts->put('pending', new MessageData(
                message: __('capell-admin::message.resource_pending', [
                    'date' => $this->record->visible_from?->diffForHumans(),
                    'name' => __('capell-layout::generic.content'),
                ]),
                type: AlertTypeEnum::Warning,
                icon: 'heroicon-o-clock',
            )),
            PublishStatusEnum::expired => $alerts->put('expired', new MessageData(
                message: __('capell-admin::message.resource_expired', [
                    'date' => $this->record->visible_until?->diffForHumans(),
                    'name' => __('capell-layout::generic.content'),
                ]),
                type: AlertTypeEnum::Warning,
                icon: 'heroicon-o-clock',
            )),
            default => $alerts,
        };

        return $alerts;
    }

    protected function loadRecord(): void
    {
        $this->record->loadMissing([
            'site' => fn (Relation $query) => $query->withTrashed(),
        ]);
    }
}
