<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Core\Support\Subscriber\Contracts\ValidatingSubscriber;

class TypeValidated implements ValidatingSubscriber
{
    /**
     * Handle the event.
     *
     * @param  string  $event  The event name
     * @param  object  $context  The context object
     */
    public function handle(string $event, object $context): void
    {
        // Handle regular events
    }

    /**
     * Validate the event.
     *
     * @param  string  $event  The event name
     * @param  object  $context  The context object
     * @return bool Returns false if validation fails, true otherwise
     */
    public function validate(string $event, object $context): bool
    {
        return $event !== 'validateCustomType';
    }
}
