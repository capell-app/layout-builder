<?php

declare(strict_types=1);

namespace Capell\Backup\Services\Import\Resolvers;

/**
 * Outcome of matching an incoming descriptor against local data. The
 * strategy string records which lookup path succeeded so the UI can
 * explain to a human why a record is about to be linked or created.
 *
 * H2.1 §6.3a extended this DTO with optional explanatory fields:
 * - reason:       short human string, why this match was chosen
 * - alternatives: other candidate matches the priority chain discovered,
 *                 ordered by descending confidence (does not include $this)
 * - warnings:     session-only notes (e.g. "checksum missing, fell back to
 *                 filename"). Not persisted through toArray() round-trips.
 */
final readonly class MatchResolution
{
    /**
     * @param  list<self>  $alternatives
     * @param  list<string>  $warnings
     */
    public function __construct(
        public int|string $localId,
        public string $strategy,
        public float $confidence = 1.0,
        public string $reason = '',
        public array $alternatives = [],
        public array $warnings = [],
    ) {}

    /**
     * @param  list<self>  $alternatives
     */
    public function withAlternatives(array $alternatives): self
    {
        return new self(
            localId: $this->localId,
            strategy: $this->strategy,
            confidence: $this->confidence,
            reason: $this->reason,
            alternatives: $alternatives,
            warnings: $this->warnings,
        );
    }
}
