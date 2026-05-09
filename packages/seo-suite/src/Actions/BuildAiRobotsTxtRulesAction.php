<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Site;
use Capell\SeoSuite\Enums\AiDiscoveryCrawlerDirectiveEnum;
use Capell\SeoSuite\Models\AiDiscoveryCrawlerRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static string run(?Site $site = null)
 */
final class BuildAiRobotsTxtRulesAction
{
    use AsAction;

    public function handle(?Site $site = null): string
    {
        $rules = AiDiscoveryCrawlerRule::query()
            ->where(function (Builder $query) use ($site): void {
                $query->whereNull('site_id');

                if ($site instanceof Site) {
                    $query->orWhere('site_id', $site->getKey());
                }
            })
            ->orderBy('provider')
            ->orderBy('user_agent')
            ->get()
            ->groupBy(fn (AiDiscoveryCrawlerRule $rule): string => implode('|', [
                $rule->provider,
                $rule->user_agent,
                $rule->path,
            ]))
            ->map(fn (Collection $ruleGroup): AiDiscoveryCrawlerRule => $ruleGroup
                ->sortByDesc(fn (AiDiscoveryCrawlerRule $rule): int => $rule->site_id === null ? 0 : 1)
                ->first())
            ->filter(fn (AiDiscoveryCrawlerRule $rule): bool => $rule->enabled)
            ->sortBy([
                ['provider', 'asc'],
                ['user_agent', 'asc'],
            ])
            ->values();

        $lines = [
            '# Capell AI Discovery managed rules',
            '# Configure crawler policy in SEO Suite settings or override individual rows per site.',
            '',
        ];

        foreach ($rules as $rule) {
            $directive = $rule->directive === AiDiscoveryCrawlerDirectiveEnum::Allow ? 'Allow' : 'Disallow';

            $lines[] = 'User-agent: ' . $rule->user_agent;
            $lines[] = $directive . ': ' . ($rule->path !== '' ? $rule->path : '/');

            if ($rule->crawl_delay_seconds !== null) {
                $lines[] = 'Crawl-delay: ' . $rule->crawl_delay_seconds;
            }

            $lines[] = '';
        }

        return rtrim(implode("\n", $lines)) . "\n";
    }
}
