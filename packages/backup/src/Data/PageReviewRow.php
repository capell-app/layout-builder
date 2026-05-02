<?php

declare(strict_types=1);

namespace Capell\Backup\Data;

/**
 * Row shown in the H2.1 wizard "Review pages" step. One entry per incoming
 * page in the package, with collision state precomputed so the table can
 * render a badge and the form can gate advance on blocking errors.
 *
 * Stable shape — persisted shorthand lives in
 * `import_sessions.page_decisions` keyed by {@see $uuid}.
 */
final readonly class PageReviewRow
{
    /** @var string */
    public const COLLISION_NONE = 'none';

    /** @var string */
    public const COLLISION_URL_LIVE = 'url_conflict_live';

    /** @var string */
    public const COLLISION_URL_WORKSPACE = 'url_conflict_workspace';

    /** @var string */
    public const ACTION_CREATE = 'create';

    /** @var string */
    public const ACTION_UPDATE = 'update';

    /** @var string */
    public const ACTION_SKIP = 'skip';

    /**
     * @param  list<array{site_id: int|null, language_id: int|null, url: string}>  $urls
     * @param  list<string>  $conflictMessages
     */
    public function __construct(
        public string $uuid,
        public ?string $title,
        public ?string $primaryUrl,
        public ?int $resolvedSiteId,
        public ?string $siteRef,
        public array $urls,
        public string $collisionState,
        public array $conflictMessages,
        public string $suggestedAction,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'primary_url' => $this->primaryUrl,
            'resolved_site_id' => $this->resolvedSiteId,
            'site_ref' => $this->siteRef,
            'urls' => $this->urls,
            'collision_state' => $this->collisionState,
            'conflict_messages' => $this->conflictMessages,
            'suggested_action' => $this->suggestedAction,
        ];
    }
}
