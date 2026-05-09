<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Http\Controllers;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Frontend\Facades\Frontend;
use Capell\SeoSuite\Actions\GenerateLlmsFullTxtAction;
use Capell\SeoSuite\Actions\PersistAiDiscoverySnapshotAction;
use Capell\SeoSuite\Actions\ResolveAiDiscoveryProfileAction;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SeoSuite\Enums\AiDiscoverySnapshotKindEnum;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use LogicException;

class LlmsFullTxtController extends BaseController
{
    public function __invoke(): Response
    {
        $site = Frontend::site();
        $language = Frontend::language();
        $reader = Frontend::contextReader();
        $siteDomain = method_exists($reader, 'domain') ? $reader->domain() : null;

        abort_unless($site instanceof Site && $language instanceof Language, 404);

        $siteProfile = ResolveAiDiscoveryProfileAction::run($site, $language);

        throw_unless($siteProfile instanceof AiDiscoverySiteProfile, LogicException::class, 'Resolving an AI Discovery site profile returned an unexpected page profile.');

        abort_if(! $siteProfile->llms_full_txt_enabled || $siteProfile->status === AiDiscoveryStatusEnum::Disabled, 404);

        $context = new AiDiscoveryRenderContextData($site, $language, $siteDomain);
        $cacheKey = sprintf(
            'capell-seo-suite:ai-discovery:%d:%s:%d:llms_full_txt',
            $site->getKey(),
            $context->domainKey(),
            $language->getKey(),
        );
        $ttlSeconds = $siteProfile->cache_ttl_seconds;
        $content = Cache::remember($cacheKey, $ttlSeconds, function () use ($cacheKey, $context, $ttlSeconds): string {
            $generatedContent = GenerateLlmsFullTxtAction::run($context);

            PersistAiDiscoverySnapshotAction::run(
                context: $context,
                kind: AiDiscoverySnapshotKindEnum::LlmsFullTxt,
                content: $generatedContent,
                cacheKey: $cacheKey,
                ttlSeconds: $ttlSeconds,
            );

            return $generatedContent;
        });

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => sprintf('public, max-age=%d', $ttlSeconds),
            'ETag' => $this->etag($content),
        ]);
    }

    private function etag(string $content): string
    {
        return '"' . hash('sha256', $content) . '"';
    }
}
