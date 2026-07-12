<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use BackedEnum;
use Capell\Core\Enums\InteractionBehavior;
use Capell\Core\Enums\InteractionTargetType;
use Capell\Core\Enums\InteractionTriggerEvent;
use Capell\LayoutBuilder\Enums\WidgetPresentationCapability;
use Capell\LayoutBuilder\Exceptions\InvalidWidgetExtensionDefinitionException;
use Spatie\LaravelData\Data;

final class WidgetExtensionCapabilitiesData extends Data
{
    /**
     * @param  list<WidgetPresentationCapability>  $presentationCapabilities
     * @param  list<InteractionTriggerEvent>  $supportedInteractionEvents
     * @param  list<InteractionBehavior>  $supportedInteractionBehaviors
     * @param  list<InteractionTargetType>  $supportedInteractionTargetTypes
     */
    public function __construct(
        public bool $supportsInteractions = false,
        public bool $requiresInstanceIdentity = false,
        public array $presentationCapabilities = [],
        public array $supportedInteractionEvents = [],
        public array $supportedInteractionBehaviors = [],
        public array $supportedInteractionTargetTypes = [],
    ) {
        $this->assertEnumList($this->presentationCapabilities, WidgetPresentationCapability::class, 'presentation capability');
        $this->assertEnumList($this->supportedInteractionEvents, InteractionTriggerEvent::class, 'interaction event');
        $this->assertEnumList($this->supportedInteractionBehaviors, InteractionBehavior::class, 'interaction behavior');
        $this->assertEnumList($this->supportedInteractionTargetTypes, InteractionTargetType::class, 'interaction target type');

        $hasInteractionModes = $this->supportedInteractionEvents !== []
            || $this->supportedInteractionBehaviors !== []
            || $this->supportedInteractionTargetTypes !== [];

        if (! $this->supportsInteractions && $hasInteractionModes) {
            throw new InvalidWidgetExtensionDefinitionException(
                'Supported interaction modes must be empty when widget interactions are disabled.',
            );
        }

        if ($this->supportsInteractions && $this->supportedInteractionEvents === []) {
            throw new InvalidWidgetExtensionDefinitionException('Interactive widgets must declare at least one supported interaction event.');
        }

        if ($this->supportsInteractions && $this->supportedInteractionBehaviors === []) {
            throw new InvalidWidgetExtensionDefinitionException('Interactive widgets must declare at least one supported interaction behavior.');
        }

        if ($this->supportsInteractions && $this->supportedInteractionTargetTypes === []) {
            throw new InvalidWidgetExtensionDefinitionException('Interactive widgets must declare at least one supported interaction target type.');
        }
    }

    /**
     * @param  list<mixed>  $values
     * @param  class-string<BackedEnum>  $enum
     */
    private function assertEnumList(array $values, string $enum, string $label): void
    {
        $seen = [];

        foreach ($values as $value) {
            if (! $value instanceof $enum || isset($seen[$value->value])) {
                throw new InvalidWidgetExtensionDefinitionException(sprintf(
                    'Widget extension %s values must be a unique list of [%s] cases.',
                    $label,
                    $enum,
                ));
            }

            $seen[$value->value] = true;
        }
    }
}
