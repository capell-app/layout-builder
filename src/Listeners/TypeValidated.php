<?php

declare(strict_types=1);

namespace Capell\Layout\Listeners;

use Capell\Admin\Contracts\ValidationSubscriber;
use Capell\Admin\Enums\ListenerEnum;
use Capell\Core\Models\Type;

class TypeValidated implements ValidationSubscriber
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
        if ($event !== ListenerEnum::ValidateCustomType->value) {
            return true;
        }

        if (! $context instanceof Type) {
            return true;
        }

        // Validate if the custom type can be deleted
        // Return false to prevent deletion, true to allow it
        // Add error message or notification if needed
        return ! ($context->name === 'custom_type' && $this->hasRelatedRecords());
    }

    private function hasRelatedRecords(): bool
    {
        // Custom logic to check if the type has related records
        return true;
        // or false
    }
}
