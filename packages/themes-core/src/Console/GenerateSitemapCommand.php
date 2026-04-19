<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Console;

use Capell\Themes\Core\SEO\SitemapGenerator;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'themes:sitemap {--output=public/sitemap.xml : Path to write the sitemap}';

    protected $description = 'Generate an XML sitemap from configured URLs';

    public function handle(): int
    {
        $outputPath = $this->option('output');

        /** @var array<int, array{url: string, lastmod?: string, changefreq?: string, priority?: float}> $configuredUrls */
        $configuredUrls = config('themes.sitemap_urls', []);

        $generator = new SitemapGenerator;

        foreach ($configuredUrls as $entry) {
            $generator->add(
                url: $entry['url'],
                lastmod: $entry['lastmod'] ?? null,
                changefreq: $entry['changefreq'] ?? 'monthly',
                priority: $entry['priority'] ?? 0.5,
            );
        }

        $success = $generator->writeTo((string) $outputPath);

        if ($success) {
            $this->info(sprintf('Sitemap written to %s (%d URLs).', $outputPath, $generator->count()));

            return self::SUCCESS;
        }

        $this->error(sprintf('Failed to write sitemap to %s.', $outputPath));

        return self::FAILURE;
    }
}
