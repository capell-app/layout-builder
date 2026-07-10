<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetSnapshots;

use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionStateWalker;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JsonException;
use Lorisleiva\Actions\Concerns\AsObject;

final readonly class RebuildPublicWidgetSnapshotsAction
{
    use AsObject;

    public function __construct(private WidgetExtensionStateWalker $walker) {}

    /** @return array<string, PublicWidgetSnapshot> */
    public function handle(FrontendRenderContextData $context): array
    {
        $page = $context->page;
        if (! $page instanceof Model || $context->site === null || $context->language === null) {
            return [];
        }

        $pageableType = $page->getMorphClass();
        $pageableId = $this->modelId($page);
        $siteId = $this->modelId($context->site);
        $languageId = $this->modelId($context->language);
        $layoutId = $context->layout === null ? null : $this->modelId($context->layout);
        $themeId = $context->theme === null ? null : $this->modelId($context->theme);
        if ($pageableId === null || $siteId === null || $languageId === null) {
            return [];
        }
        $ownerRevision = $this->ownerRevision($context);
        $retentionSeconds = max(1, $this->configuredSeconds('ttl_seconds', 86400))
            + max(0, $this->configuredSeconds('stale_while_revalidate_seconds', 3600));
        $expiresAt = now()->addSeconds($retentionSeconds);
        $snapshots = [];

        foreach ($this->walker->fromContext($context) as $discovered) {
            $fingerprint = hash('sha256', $this->canonicalJson([
                'site' => $siteId,
                'pageable_type' => $pageableType,
                'pageable_id' => $pageableId,
                'language' => $languageId,
                'layout' => $layoutId,
                'theme' => $themeId,
                'profile' => 'blade',
                'revision' => $ownerRevision,
                'instance' => $discovered->instanceId,
                'definition_version' => $discovered->definition->stateVersion,
                'widget' => $discovered->widget,
            ]));

            $snapshot = DB::transaction(function () use (
                $siteId,
                $pageableType,
                $pageableId,
                $languageId,
                $layoutId,
                $themeId,
                $ownerRevision,
                $fingerprint,
                $discovered,
                $expiresAt,
            ): PublicWidgetSnapshot {
                $existing = PublicWidgetSnapshot::query()
                    ->where('context_fingerprint', $fingerprint)
                    ->where('target_instance_id', $discovered->instanceId)
                    ->first();

                if ($existing instanceof PublicWidgetSnapshot) {
                    return $existing;
                }

                PublicWidgetSnapshot::query()
                    ->where('site_id', $siteId)
                    ->where('pageable_type', $pageableType)
                    ->where('pageable_id', $pageableId)
                    ->where('language_id', $languageId)
                    ->where('target_instance_id', $discovered->instanceId)
                    ->whereNull('superseded_at')
                    ->whereNull('revoked_at')
                    ->update(['superseded_at' => now(), 'expires_at' => $expiresAt]);

                return PublicWidgetSnapshot::query()->create([
                    'site_id' => $siteId,
                    'pageable_type' => $pageableType,
                    'pageable_id' => $pageableId,
                    'language_id' => $languageId,
                    'layout_id' => $layoutId,
                    'theme_id' => $themeId,
                    'render_profile' => 'blade',
                    'owner_revision' => $ownerRevision,
                    'context_fingerprint' => $fingerprint,
                    'target_instance_id' => $discovered->instanceId,
                    'widget_key' => $discovered->definition->key,
                    'definition_state_version' => $discovered->definition->stateVersion,
                    'encrypted_payload' => ['widget' => $discovered->widget],
                    'expires_at' => $expiresAt,
                ]);
            });

            $snapshots[$discovered->instanceId] = $snapshot;
        }

        return $snapshots;
    }

    private function ownerRevision(FrontendRenderContextData $context): string
    {
        $page = $context->page;
        $translation = $page instanceof Model && $page->relationLoaded('translation')
            ? $page->getRelation('translation')
            : null;

        $pageUpdatedAt = $page instanceof Model ? $page->getAttribute('updated_at') : null;
        $translationUpdatedAt = $translation instanceof Model ? $translation->getAttribute('updated_at') : null;

        return hash('sha256', $this->canonicalJson([
            'page_updated_at' => $pageUpdatedAt instanceof DateTimeInterface ? $pageUpdatedAt->format('U.u') : null,
            'translation_updated_at' => $translationUpdatedAt instanceof DateTimeInterface ? $translationUpdatedAt->format('U.u') : null,
            'content' => $translation instanceof Model ? $translation->getAttribute('content') : null,
        ]));
    }

    private function modelId(Model $model): ?int
    {
        $identifier = $model->getKey();

        return is_int($identifier) && $identifier > 0 ? $identifier : null;
    }

    private function configuredSeconds(string $key, int $default): int
    {
        $value = config('capell-layout-builder.public_widget_snapshots.' . $key, $default);

        return is_int($value) ? $value : $default;
    }

    /** @param array<string, mixed> $value */
    private function canonicalJson(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            return serialize($value);
        }
    }
}
