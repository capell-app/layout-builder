<?php

declare(strict_types=1);

namespace Capell\Layout\Listeners;

use Capell\Admin\Enums\ListenerEnum;
use Capell\Admin\Filament\Resources\Layouts\Pages\EditLayout;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Contracts\EventSubscriber;
use Capell\Layout\Enums\LivewireComponentsEnum;

class AfterRecordSaved implements EventSubscriber
{
    public function handle(string $event, object $context): void
    {
        if ($event !== ListenerEnum::AfterSave->value) {
            return;
        }

        if ($context instanceof EditPage || $context instanceof EditLayout) {
            $context->dispatch('save-layout')->to(LivewireComponentsEnum::LayoutBuilder->value);
        }
    }
}
