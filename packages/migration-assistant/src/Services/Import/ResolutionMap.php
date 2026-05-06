<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Services\Import;

use Capell\MigrationAssistant\Services\Import\Resolvers\MatchResolution;

/**
 * Maps shared-relation refs (e.g. "layout:7") from an incoming package to
 * the local record that should stand in for them. Also tracks refs that
 * had no match so the wizard can surface them for human resolution.
 */
final readonly class ResolutionMap
{
    /**
     * @param  array<string, MatchResolution>  $resolved
     * @param  array<int, string>  $unresolved
     */
    public function __construct(
        public array $resolved,
        public array $unresolved,
    ) {}

    public function hasUnresolved(): bool
    {
        return $this->unresolved !== [];
    }

    public function localIdFor(string $ref): int|string|null
    {
        return $this->resolved[$ref]->localId ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $resolved = [];
        foreach ($this->resolved as $ref => $resolution) {
            $resolved[$ref] = self::encodeResolution($resolution);
        }

        return [
            'resolved' => $resolved,
            'unresolved' => $this->unresolved,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function encodeResolution(MatchResolution $resolution): array
    {
        return [
            'local_id' => $resolution->localId,
            'strategy' => $resolution->strategy,
            'confidence' => $resolution->confidence,
            'reason' => $resolution->reason,
            'alternatives' => array_map(self::encodeResolution(...), $resolution->alternatives),
        ];
    }
}
