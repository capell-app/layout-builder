<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetSnapshots;

use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Actions\WidgetExtensions\RestoreWidgetInteractionContextAction;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotFingerprint;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotLocatorCodec;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotRequestDomain;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static array{snapshot: PublicWidgetSnapshot, context: FrontendRenderContextData, widget: array<string, mixed>}|null run(string $locator) */
final readonly class ResolvePublicWidgetSnapshotAction
{
    use AsFake;
    use AsObject;

    public function __construct(
        private WidgetSnapshotLocatorCodec $codec,
        private WidgetSnapshotFingerprint $fingerprint,
        private WidgetSnapshotRequestDomain $requestDomain,
    ) {}

    /** @return array{snapshot: PublicWidgetSnapshot, context: FrontendRenderContextData, widget: array<string, mixed>}|null */
    public function handle(string $locator): ?array
    {
        $decoded = $this->codec->decode($locator);
        if ($decoded === null) {
            return null;
        }

        $snapshot = PublicWidgetSnapshot::query()->find($decoded->snapshotId);
        if (! $snapshot instanceof PublicWidgetSnapshot
            || ! $snapshot->isAvailable()
            || $snapshot->pageable_type !== $decoded->pageableType
            || $snapshot->pageable_id !== $decoded->pageableId
            || ! hash_equals($snapshot->target_instance_id, $decoded->targetInstanceId)) {
            return null;
        }
        if ($this->requestDomain->resolve($snapshot->site_id, $snapshot->language_id) === null) {
            return null;
        }

        $context = RestoreWidgetInteractionContextAction::run([
            'version' => 1,
            'purpose' => 'widget-interaction',
            'site_id' => $snapshot->site_id,
            'page_type' => $snapshot->pageable_type,
            'page_id' => $snapshot->pageable_id,
            'language_id' => $snapshot->language_id,
            'layout_id' => $snapshot->layout_id,
            'theme_id' => $snapshot->theme_id,
        ]);
        $payload = $snapshot->encrypted_payload;
        $widget = $payload['widget'] ?? null;

        if (! $context instanceof FrontendRenderContextData || ! is_array($widget)) {
            return null;
        }

        $type = $widget['type'] ?? null;
        $data = $widget['data'] ?? null;
        if (! is_string($type) || $type !== $snapshot->widget_key || ! is_array($data)) {
            return null;
        }

        $capell = $data['__capell'] ?? null;
        if (! is_array($capell) || ($capell['instance_id'] ?? null) !== $snapshot->target_instance_id) {
            return null;
        }

        if ($snapshot->render_profile !== 'blade' || ! hash_equals($snapshot->context_fingerprint, $this->fingerprint->make(
            $snapshot->site_id,
            $snapshot->pageable_type,
            $snapshot->pageable_id,
            $snapshot->language_id,
            $snapshot->layout_id,
            $snapshot->theme_id,
            $snapshot->render_profile,
            $snapshot->owner_revision,
            $snapshot->target_instance_id,
            $snapshot->definition_state_version,
            $widget,
        ))) {
            return null;
        }

        return ['snapshot' => $snapshot, 'context' => $context, 'widget' => $widget];
    }
}
