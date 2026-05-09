<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Site;
use Capell\SeoSuite\Enums\AiDiscoveryCrawlerDirectiveEnum;
use Capell\SeoSuite\Enums\AiDiscoveryCrawlerPurposeEnum;
use Capell\SeoSuite\Models\AiDiscoveryCrawlerRule;
use Capell\SeoSuite\Settings\SeoSuiteSettings;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelSettings\SettingsRepositories\SettingsRepository;
use Throwable;

/**
 * @method static int run(?Site $site = null)
 */
final class SeedDefaultAiCrawlerRulesAction
{
    use AsAction;

    public function handle(?Site $site = null): int
    {
        foreach ($this->defaults() as $rule) {
            AiDiscoveryCrawlerRule::query()->updateOrCreate(
                [
                    'site_id' => $site?->getKey(),
                    'provider' => $rule['provider'],
                    'user_agent' => $rule['user_agent'],
                    'path' => $rule['path'],
                ],
                [
                    'purpose' => $rule['purpose']->value,
                    'directive' => $rule['directive']->value,
                    'enabled' => true,
                    'source_url' => $rule['source_url'],
                    'notes' => $rule['notes'],
                ],
            );
        }

        return count($this->defaults());
    }

    /**
     * @return list<array{provider: string, user_agent: string, purpose: AiDiscoveryCrawlerPurposeEnum, directive: AiDiscoveryCrawlerDirectiveEnum, path: string, source_url: string, notes: string}>
     */
    private function defaults(): array
    {
        $configuredRules = config('capell-seo-suite.ai_discovery.default_crawler_rules');

        if (! is_array($configuredRules)) {
            return $this->applyCrawlerPolicy($this->fallbackDefaults());
        }

        $rules = [];

        foreach ($configuredRules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $purpose = AiDiscoveryCrawlerPurposeEnum::tryFrom($this->stringValue($rule['purpose'] ?? 'unknown'));
            $directive = AiDiscoveryCrawlerDirectiveEnum::tryFrom($this->stringValue($rule['directive'] ?? 'disallow'));
            $provider = $this->stringValue($rule['provider'] ?? '');
            $userAgent = $this->stringValue($rule['user_agent'] ?? '');
            if ($purpose === null) {
                continue;
            }

            if ($directive === null) {
                continue;
            }

            if ($provider === '') {
                continue;
            }

            if ($userAgent === '') {
                continue;
            }

            $path = $this->stringValue($rule['path'] ?? '/');

            $rules[] = [
                'provider' => $provider,
                'user_agent' => $userAgent,
                'purpose' => $purpose,
                'directive' => $directive,
                'path' => $path !== '' ? $path : '/',
                'source_url' => $this->stringValue($rule['source_url'] ?? ''),
                'notes' => $this->stringValue($rule['notes'] ?? ''),
            ];
        }

        return $this->applyCrawlerPolicy($rules !== [] ? $rules : $this->fallbackDefaults());
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @param  list<array{provider: string, user_agent: string, purpose: AiDiscoveryCrawlerPurposeEnum, directive: AiDiscoveryCrawlerDirectiveEnum, path: string, source_url: string, notes: string}>  $rules
     * @return list<array{provider: string, user_agent: string, purpose: AiDiscoveryCrawlerPurposeEnum, directive: AiDiscoveryCrawlerDirectiveEnum, path: string, source_url: string, notes: string}>
     */
    private function applyCrawlerPolicy(array $rules): array
    {
        $configuredPolicy = $this->selectedCrawlerPolicy();
        $presets = config('capell-seo-suite.ai_discovery.crawler_policy_presets');

        if (! is_array($presets)) {
            return $rules;
        }

        $preset = $presets[$configuredPolicy] ?? null;

        if (! is_array($preset)) {
            return $rules;
        }

        $presetRules = $preset['rules'] ?? null;

        if (! is_array($presetRules)) {
            return $rules;
        }

        return array_map(function (array $rule) use ($presetRules): array {
            $directive = $presetRules[$rule['user_agent']] ?? null;

            if (! is_string($directive)) {
                return $rule;
            }

            $policyDirective = AiDiscoveryCrawlerDirectiveEnum::tryFrom(trim($directive));

            if (! $policyDirective instanceof AiDiscoveryCrawlerDirectiveEnum) {
                return $rule;
            }

            $rule['directive'] = $policyDirective;

            return $rule;
        }, $rules);
    }

    private function selectedCrawlerPolicy(): string
    {
        try {
            $repository = resolve(SettingsRepository::class);

            if ($repository->checkIfPropertyExists(SeoSuiteSettings::group(), 'ai_discovery_crawler_policy')) {
                $settings = resolve(SeoSuiteSettings::class);

                if (trim($settings->ai_discovery_crawler_policy) !== '') {
                    return trim($settings->ai_discovery_crawler_policy);
                }
            }
        } catch (Throwable) {
            //
        }

        $configuredPolicy = $this->stringValue(config('capell-seo-suite.ai_discovery.crawler_policy'));

        return $configuredPolicy !== ''
            ? $configuredPolicy
            : 'search_visible_training_restricted';
    }

    /**
     * @return list<array{provider: string, user_agent: string, purpose: AiDiscoveryCrawlerPurposeEnum, directive: AiDiscoveryCrawlerDirectiveEnum, path: string, source_url: string, notes: string}>
     */
    private function fallbackDefaults(): array
    {
        return [
            [
                'provider' => 'OpenAI',
                'user_agent' => 'OAI-SearchBot',
                'purpose' => AiDiscoveryCrawlerPurposeEnum::Search,
                'directive' => AiDiscoveryCrawlerDirectiveEnum::Allow,
                'path' => '/',
                'source_url' => 'https://platform.openai.com/docs/bots',
                'notes' => 'ChatGPT search visibility crawler.',
            ],
            [
                'provider' => 'OpenAI',
                'user_agent' => 'GPTBot',
                'purpose' => AiDiscoveryCrawlerPurposeEnum::Training,
                'directive' => AiDiscoveryCrawlerDirectiveEnum::Disallow,
                'path' => '/',
                'source_url' => 'https://platform.openai.com/docs/bots',
                'notes' => 'OpenAI training crawler control.',
            ],
            [
                'provider' => 'OpenAI',
                'user_agent' => 'ChatGPT-User',
                'purpose' => AiDiscoveryCrawlerPurposeEnum::UserTriggered,
                'directive' => AiDiscoveryCrawlerDirectiveEnum::Allow,
                'path' => '/',
                'source_url' => 'https://platform.openai.com/docs/bots',
                'notes' => 'User-triggered ChatGPT fetch control; robots.txt may not apply to every user-initiated action.',
            ],
            [
                'provider' => 'Anthropic',
                'user_agent' => 'ClaudeBot',
                'purpose' => AiDiscoveryCrawlerPurposeEnum::Training,
                'directive' => AiDiscoveryCrawlerDirectiveEnum::Disallow,
                'path' => '/',
                'source_url' => 'https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler',
                'notes' => 'Anthropic crawler control.',
            ],
            [
                'provider' => 'Anthropic',
                'user_agent' => 'Claude-SearchBot',
                'purpose' => AiDiscoveryCrawlerPurposeEnum::Search,
                'directive' => AiDiscoveryCrawlerDirectiveEnum::Allow,
                'path' => '/',
                'source_url' => 'https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler',
                'notes' => 'Anthropic search visibility crawler.',
            ],
            [
                'provider' => 'Anthropic',
                'user_agent' => 'Claude-User',
                'purpose' => AiDiscoveryCrawlerPurposeEnum::UserTriggered,
                'directive' => AiDiscoveryCrawlerDirectiveEnum::Allow,
                'path' => '/',
                'source_url' => 'https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler',
                'notes' => 'User-triggered Claude fetch control.',
            ],
            [
                'provider' => 'Perplexity',
                'user_agent' => 'PerplexityBot',
                'purpose' => AiDiscoveryCrawlerPurposeEnum::Search,
                'directive' => AiDiscoveryCrawlerDirectiveEnum::Allow,
                'path' => '/',
                'source_url' => 'https://docs.perplexity.ai/guides/bots',
                'notes' => 'Perplexity search indexing crawler.',
            ],
            [
                'provider' => 'Google',
                'user_agent' => 'Google-Extended',
                'purpose' => AiDiscoveryCrawlerPurposeEnum::Training,
                'directive' => AiDiscoveryCrawlerDirectiveEnum::Disallow,
                'path' => '/',
                'source_url' => 'https://developers.google.com/search/docs/crawling-indexing/google-common-crawlers',
                'notes' => 'Gemini training and grounding control token.',
            ],
            [
                'provider' => 'Common Crawl',
                'user_agent' => 'CCBot',
                'purpose' => AiDiscoveryCrawlerPurposeEnum::GenericCrawl,
                'directive' => AiDiscoveryCrawlerDirectiveEnum::Disallow,
                'path' => '/',
                'source_url' => 'https://commoncrawl.org/ccbot',
                'notes' => 'Common Crawl dataset crawler.',
            ],
        ];
    }
}
