<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Sections\Widgets;

use Capell\Admin\Data\MessageData;
use Capell\Admin\Enums\AlertTypeEnum;
use Capell\Admin\Filament\Concerns\HasBlankPlaceholder;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\LayoutBuilder\Models\Section;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\FormBuilder\Concerns\InteractsWithFormBuilder;
use Filament\FormBuilder\Contracts\HasFormBuilder;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

#[On('refresh-alerts')]
class SectionAlertsWidget extends Widget implements HasActions, HasFormBuilder
{
    use HasBlankPlaceholder;
    use InteractsWithActions;
    use InteractsWithFormBuilder;

    public ?Section $record = null;

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

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
     * @return Collection<string, MessageData>
     */
    #[Computed]
    public function alerts(): Collection
    {
        $alerts = collect();

        if ($this->record->trashed()) {
            $alerts->put('trashed', new MessageData(
                message: __(
                    'capell-admin::message.resource_deleted',
                    ['name' => __('capell-layout-builder::generic.content')],
                ),
                type: AlertTypeEnum::Warning,
                icon: 'heroicon-m-exclamation-triangle',
            ));
        }

        switch ($this->record->publish_status) {
            case PublishStatusEnum::pending:
                $alerts->put('pending', new MessageData(
                    message: __('capell-admin::message.resource_pending', [
                        'date' => $this->record->visible_from?->diffForHumans(),
                        'name' => __('capell-layout-builder::generic.content'),
                    ]),
                    type: AlertTypeEnum::Warning,
                    icon: 'heroicon-o-clock',
                ));
                break;
            case PublishStatusEnum::expired:
                $alerts->put('expired', new MessageData(
                    message: __('capell-admin::message.resource_expired', [
                        'date' => $this->record->visible_until?->diffForHumans(),
                        'name' => __('capell-layout-builder::generic.content'),
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
        $this->record->loadMissing([
            'site' => fn (Relation $query) => $query->withTrashed(),
        ]);
    }
}
