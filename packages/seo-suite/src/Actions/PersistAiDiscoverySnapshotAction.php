<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Page;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SeoSuite\Enums\AiDiscoverySnapshotKindEnum;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoverySnapshot;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static AiDiscoverySnapshot run(AiDiscoveryRenderContextData $context, AiDiscoverySnapshotKindEnum $kind, string $content, string $cacheKey, ?int $ttlSeconds = null, ?Page $page = null, string $status = 'fresh', ?string $errorMessage = null)
 */
final class PersistAiDiscoverySnapshotAction
{
    use AsAction;

    public function handle(
        AiDiscoveryRenderContextData $context,
        AiDiscoverySnapshotKindEnum $kind,
        string $content,
        string $cacheKey,
        ?int $ttlSeconds = null,
        ?Page $page = null,
        string $status = AiDiscoveryStatusEnum::Fresh->value,
        ?string $errorMessage = null,
    ): AiDiscoverySnapshot {
        $generatedAt = CarbonImmutable::instance(now());

        return AiDiscoverySnapshot::query()->updateOrCreate(
            [
                'site_id' => $context->site->getKey(),
                'language_id' => $context->language->getKey(),
                'kind' => $kind->value,
                'context_key' => $this->contextKey($context, $page),
            ],
            [
                'site_domain_id' => $context->siteDomain?->getKey(),
                'page_id' => $page?->getKey(),
                'content_hash' => hash('sha256', $content),
                'byte_size' => strlen($content),
                'cache_key' => $cacheKey,
                'generated_at' => $generatedAt,
                'expires_at' => $this->expiresAt($generatedAt, $ttlSeconds),
                'status' => $status,
                'error_message' => $errorMessage,
            ],
        );
    }

    private function contextKey(AiDiscoveryRenderContextData $context, ?Page $page): string
    {
        return $context->domainKey() . ':' . ($page?->getKey() ?? 'site');
    }

    private function expiresAt(CarbonImmutable $generatedAt, ?int $ttlSeconds): ?CarbonImmutable
    {
        if ($ttlSeconds === null) {
            return null;
        }

        return $generatedAt->addSeconds($ttlSeconds);
    }
}
