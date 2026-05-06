<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\LaravelData\Data;

final class CapabilityInvocationData extends Data
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly CapabilityData $capability,
        public readonly array $payload,
        public readonly ?AuthenticatedAgentBridgeClientData $client = null,
        public readonly ?Authenticatable $user = null,
        public readonly array $meta = [],
    ) {}
}
