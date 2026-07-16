<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetSnapshots;

use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionStateWalker;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotFingerprint;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final readonly class RebuildPublicWidgetSnapshotsAction
{
    use AsFake;
    use AsObject;

    private const int MAX_CURRENT_CREATE_ATTEMPTS = 3;

    public function __construct(
        private WidgetExtensionStateWalker $walker,
        private WidgetSnapshotFingerprint $fingerprint,
    ) {}

    /** @return array<string, PublicWidgetSnapshot> */
    public function handle(FrontendRenderContextData $context): array
    {
        if (! CapellCore::isPackageInstalled(LayoutBuilderServiceProvider::$packageName)) {
            return [];
        }

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
            $fingerprint = $this->fingerprint->make(
                $siteId,
                $pageableType,
                $pageableId,
                $languageId,
                $layoutId,
                $themeId,
                'blade',
                $ownerRevision,
                $discovered->instanceId,
                $discovered->definition->stateVersion,
                $discovered->widget,
            );
            $currentKey = hash('sha256', json_encode([
                'site' => $siteId,
                'pageable_type' => $pageableType,
                'pageable_id' => $pageableId,
                'language' => $languageId,
                'layout' => $layoutId,
                'theme' => $themeId,
                'render_profile' => 'blade',
                'instance' => $discovered->instanceId,
            ], JSON_THROW_ON_ERROR));
            $attributes = [
                'site_id' => $siteId,
                'pageable_type' => $pageableType,
                'pageable_id' => $pageableId,
                'language_id' => $languageId,
                'layout_id' => $layoutId,
                'theme_id' => $themeId,
                'render_profile' => 'blade',
                'owner_revision' => $ownerRevision,
                'context_fingerprint' => $fingerprint,
                'current_key' => $currentKey,
                'target_instance_id' => $discovered->instanceId,
                'widget_key' => $discovered->definition->key,
                'definition_state_version' => $discovered->definition->stateVersion,
                'encrypted_payload' => ['widget' => $discovered->widget],
                'expires_at' => null,
            ];
            $snapshot = null;

            for ($attempt = 1; $attempt <= self::MAX_CURRENT_CREATE_ATTEMPTS; $attempt++) {
                try {
                    $snapshot = DB::transaction(function () use (
                        $siteId,
                        $pageableType,
                        $pageableId,
                        $languageId,
                        $fingerprint,
                        $currentKey,
                        $discovered,
                        $expiresAt,
                        $attributes,
                    ): PublicWidgetSnapshot {
                        $existing = PublicWidgetSnapshot::query()
                            ->where('current_key', $currentKey)
                            ->lockForUpdate()
                            ->first();
                        if ($existing instanceof PublicWidgetSnapshot
                            && $existing->isAvailable()
                            && hash_equals($existing->context_fingerprint, $fingerprint)) {
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
                            ->lockForUpdate()
                            ->update([
                                'current_key' => null,
                                'superseded_at' => now(),
                                'expires_at' => $expiresAt,
                            ]);

                        try {
                            return PublicWidgetSnapshot::query()->create($attributes);
                        } catch (QueryException $queryException) {
                            $winner = PublicWidgetSnapshot::query()
                                ->where('current_key', $currentKey)
                                ->lockForUpdate()
                                ->first();
                            if ($winner instanceof PublicWidgetSnapshot
                                && hash_equals($winner->context_fingerprint, $fingerprint)) {
                                return $winner;
                            }
                            if ($winner instanceof PublicWidgetSnapshot) {
                                $winner->forceFill([
                                    'current_key' => null,
                                    'superseded_at' => now(),
                                    'expires_at' => $expiresAt,
                                ])->save();

                                return PublicWidgetSnapshot::query()->create($attributes);
                            }

                            throw $queryException;
                        }
                    });

                    break;
                } catch (QueryException) {
                    $winner = PublicWidgetSnapshot::query()->where('current_key', $currentKey)->first();
                    if ($winner instanceof PublicWidgetSnapshot
                        && hash_equals($winner->context_fingerprint, $fingerprint)) {
                        $snapshot = $winner;

                        break;
                    }
                }
            }

            if (! $snapshot instanceof PublicWidgetSnapshot) {
                Log::critical('Unable to establish the requested public widget snapshot after bounded race recovery.', [
                    'widget_key' => $discovered->definition->key,
                    'attempts' => self::MAX_CURRENT_CREATE_ATTEMPTS,
                ]);

                continue;
            }

            $snapshots[$discovered->instanceId] = $snapshot;
        }

        $currentSnapshotIds = array_map(
            static fn (PublicWidgetSnapshot $snapshot): int => $snapshot->id,
            array_values($snapshots),
        );
        PublicWidgetSnapshot::query()
            ->where('site_id', $siteId)
            ->where('pageable_type', $pageableType)
            ->where('pageable_id', $pageableId)
            ->where('language_id', $languageId)
            ->whereNull('superseded_at')
            ->whereNull('revoked_at')
            ->when($currentSnapshotIds !== [], fn ($query) => $query->whereNotIn('id', $currentSnapshotIds))
            ->update(['current_key' => null, 'superseded_at' => now(), 'expires_at' => $expiresAt]);

        return $snapshots;
    }

    public function ownerRevision(FrontendRenderContextData $context): string
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
