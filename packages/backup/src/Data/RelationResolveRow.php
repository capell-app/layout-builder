<?php

declare(strict_types=1);

namespace Capell\Backup\Data;

/**
 * Row shown in the H2.1 wizard "Resolve relations" step. One entry per
 * shared-relation ref that appeared in the incoming package, grouped by
 * relation kind (layouts, types, sites, media).
 *
 * Mirrors the shape convention of {@see PageReviewRow} — plain readonly
 * DTO with a `toArray()` projection so Livewire + Blade can consume it
 * without leaking the object graph.
 *
 * Persisted shorthand lives in `import_sessions.relation_decisions`
 * keyed by {@see $ref}.
 */
final readonly class RelationResolveRow
{
    public const ACTION_USE_EXISTING = 'use_existing';

    public const ACTION_CREATE_NEW = 'create_new';

    public const ACTION_CLONE_IMPORTED = 'clone_imported';

    public const ACTION_UPDATE_EXISTING = 'update_existing';

    public const ACTION_SKIP = 'skip';

    public const GROUP_LAYOUTS = 'layouts';

    public const GROUP_TYPES = 'types';

    public const GROUP_SITES = 'sites';

    public const GROUP_MEDIA = 'media';

    /**
     * @param  array{local_id: int|string, strategy: string, confidence: float, reason: string}|null  $topMatch
     * @param  list<array{strategy: string, confidence: float, reason: string, local_id: int|string}>  $alternatives
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $group,
        public string $ref,
        public ?array $topMatch,
        public array $alternatives,
        public array $warnings,
        public string $suggestedAction,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'group' => $this->group,
            'ref' => $this->ref,
            'top_match' => $this->topMatch,
            'alternatives' => $this->alternatives,
            'warnings' => $this->warnings,
            'suggested_action' => $this->suggestedAction,
        ];
    }
}
