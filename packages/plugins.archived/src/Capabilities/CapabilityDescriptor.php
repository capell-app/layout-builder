<?php

declare(strict_types=1);

namespace Capell\Plugins\Capabilities;

use Capell\Plugins\Enums\Capability;
use Capell\Plugins\Enums\CapabilityWarningLevel;

final readonly class CapabilityDescriptor
{
    public function __construct(
        public Capability $capability,
        public CapabilityWarningLevel $warningLevel,
        public string $title,
        public string $summary,
        public ?string $parameter = null,
    ) {}

    public function toManifestString(): string
    {
        return $this->parameter === null
            ? $this->capability->value
            : sprintf('%s:%s', $this->capability->value, $this->parameter);
    }
}
