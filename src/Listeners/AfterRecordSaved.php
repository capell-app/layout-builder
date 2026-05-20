<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Core\Contracts\EventSubscriber;
use Capell\LayoutBuilder\Enums\LivewireComponentsEnum;

class AfterRecordSaved implements EventSubscriber
{
    public function handle(string $event, object $context): void
    {
        if ($event !== 'afterSave') {
            return;
        }

        if (is_a($context, 'Capell\\Admin\\Filament\\Resources\\Pages\\Pages\\EditPage') || is_a($context, 'Capell\\Admin\\Filament\\Resources\\Layouts\\Pages\\EditLayout')) {
            $context->dispatch('save-layout', withNotifications: true)->to(LivewireComponentsEnum::LayoutBuilder->value);
        }
    }
}
