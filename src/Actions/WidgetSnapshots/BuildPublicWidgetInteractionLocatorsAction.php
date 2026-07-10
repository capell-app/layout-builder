<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetSnapshots;

use Capell\Frontend\Contracts\PublicWidgetInteractionLocatorBuilder;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Data\WidgetSnapshots\WidgetSnapshotLocatorData;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotLocatorCodec;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final readonly class BuildPublicWidgetInteractionLocatorsAction implements PublicWidgetInteractionLocatorBuilder
{
    public function __construct(
        private RebuildPublicWidgetSnapshotsAction $rebuilder,
        private WidgetSnapshotLocatorCodec $codec,
    ) {}

    /** @return array<string, string> */
    public function build(FrontendRenderContextData $context): array
    {
        try {
            $page = $context->page;
            if (! $page instanceof Model) {
                return [];
            }

            $locators = [];
            foreach ($this->rebuilder->handle($context) as $instanceId => $snapshot) {
                if (! is_int($snapshot->getKey())) {
                    continue;
                }

                $locator = $this->codec->encode(new WidgetSnapshotLocatorData(
                    version: WidgetSnapshotLocatorCodec::VERSION,
                    purpose: WidgetSnapshotLocatorCodec::PURPOSE,
                    snapshotId: $snapshot->getKey(),
                    pageableType: $snapshot->pageable_type,
                    pageableId: $snapshot->pageable_id,
                    targetInstanceId: $instanceId,
                ));
                $locators[$instanceId] = url('/_capell/layout-widgets/' . rawurlencode($locator));
            }

            return $locators;
        } catch (Throwable $throwable) {
            report($throwable);

            return [];
        }
    }
}
