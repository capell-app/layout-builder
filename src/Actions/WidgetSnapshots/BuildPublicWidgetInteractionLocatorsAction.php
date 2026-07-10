<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetSnapshots;

use Capell\Frontend\Contracts\PublicWidgetInteractionLocatorBuilder;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Data\WidgetSnapshots\WidgetSnapshotLocatorData;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotLocatorCodec;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotRequestDomain;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final readonly class BuildPublicWidgetInteractionLocatorsAction implements PublicWidgetInteractionLocatorBuilder
{
    public function __construct(
        private RebuildPublicWidgetSnapshotsAction $revisionResolver,
        private WidgetSnapshotLocatorCodec $codec,
        private WidgetSnapshotRequestDomain $requestDomain,
    ) {}

    /** @return array<string, string> */
    public function build(FrontendRenderContextData $context): array
    {
        try {
            $page = $context->page;
            $siteId = $context->site?->getKey();
            $languageId = $context->language?->getKey();
            if (! $page instanceof Model || ! is_int($siteId) || ! is_int($languageId)) {
                return [];
            }
            $domain = $this->requestDomain->resolve($siteId, $languageId);
            if ($domain === null) {
                return [];
            }

            $locators = [];
            $snapshots = PublicWidgetSnapshot::query()
                ->where('site_id', $siteId)
                ->where('pageable_type', $page->getMorphClass())
                ->where('pageable_id', $page->getKey())
                ->where('language_id', $languageId)
                ->where('layout_id', $context->layout?->getKey())
                ->where('theme_id', $context->theme?->getKey())
                ->where('render_profile', 'blade')
                ->where('owner_revision', $this->revisionResolver->ownerRevision($context))
                ->whereNull('superseded_at')
                ->whereNull('revoked_at')
                ->whereNull('expires_at')
                ->whereNotNull('current_key')
                ->get();

            foreach ($snapshots as $snapshot) {
                if (! is_int($snapshot->getKey())) {
                    continue;
                }

                $instanceId = $snapshot->target_instance_id;

                $locator = $this->codec->encode(new WidgetSnapshotLocatorData(
                    version: WidgetSnapshotLocatorCodec::VERSION,
                    purpose: WidgetSnapshotLocatorCodec::PURPOSE,
                    snapshotId: $snapshot->getKey(),
                    pageableType: $snapshot->pageable_type,
                    pageableId: $snapshot->pageable_id,
                    targetInstanceId: $instanceId,
                ));
                $locators[$instanceId] = $this->requestDomain->locatorUrl($domain, $locator);
            }

            return $locators;
        } catch (Throwable $throwable) {
            report($throwable);

            return [];
        }
    }
}
