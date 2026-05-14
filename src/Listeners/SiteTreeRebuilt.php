<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

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
        if ($event !== 'siteTreeRebuilt') {
            return;
        }

        if (is_a($context, 'Capell\\Admin\\Livewire\\Header\\AdminTools') && $context->siteTree()) {
            Notification::make('content_tree')
                ->status('warning')
                ->title(__('capell-layout-builder::generic.fixed_content_tree'))
                ->send();

            return;
        }
    }
}
