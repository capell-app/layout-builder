<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Admin\Enums\ListenerEnum;
use Capell\Admin\Livewire\Header\AdminTools;
use Capell\Core\Contracts\EventSubscriber;
use Filament\Notifications\Notification;

class SiteTreeRebuilt implements EventSubscriber
{
    /**
     * Handle the event.
     *
     * @param  string  $event  The event name
     * @param  object  $context  The context object
     */
    public function handle(string $event, object $context): void
    {
        if ($event !== ListenerEnum::SiteTreeRebuilt->value) {
            return;
        }

        if ($context instanceof AdminTools && $context->siteTree()) {
            Notification::make('content_tree')
                ->status('warning')
                ->title(__('capell-layout-builder::generic.fixed_content_tree'))
                ->send();

            return;
        }
    }
}
