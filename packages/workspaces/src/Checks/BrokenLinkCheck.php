<?php

declare(strict_types=1);

namespace Capell\Workspaces\Checks;

use Capell\Workspaces\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BrokenLinkCheck implements PublishCheck
{
    public function identifier(): string
    {
        return 'broken-links';
    }

    public function label(): string
    {
        return 'Broken Links';
    }

    public function run(Workspace $workspace): PublishCheckResult
    {
        if (! Schema::hasTable('page_urls') || ! Schema::hasColumn('pages', 'body')) {
            return new PublishCheckResult(
                identifier: $this->identifier(),
                label: $this->label(),
                severity: PublishCheckSeverity::Info,
            );
        }

        $columns = ['uuid', 'body'];
        if (Schema::hasColumn('pages', 'slug')) {
            $columns[] = 'slug';
        }

        $pages = DB::table('pages')
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('body')
            ->select($columns)
            ->get();

        // Build a map of pageUuid => hrefs[] and collect all unique hrefs in one pass.
        $pageHrefMap = [];
        $allHrefs = [];

        foreach ($pages as $page) {
            $internalLinks = $this->extractInternalLinks((string) $page->body);

            if ($internalLinks !== []) {
                $pageHrefMap[$page->uuid] = ['slug' => $page->slug ?? null, 'hrefs' => $internalLinks];
                foreach ($internalLinks as $href) {
                    $allHrefs[$href] = true;
                }
            }
        }

        $messages = [];
        $entityRefs = [];

        if ($allHrefs !== []) {
            $existingUrls = DB::table('page_urls')
                ->whereIn('url', array_keys($allHrefs))
                ->where(function ($query) use ($workspace): void {
                    $query->where('workspace_id', 0)
                        ->orWhere('workspace_id', $workspace->id);
                })
                ->pluck('url')
                ->flip()
                ->all();

            foreach ($pageHrefMap as $pageUuid => $pageData) {
                foreach ($pageData['hrefs'] as $href) {
                    if (! array_key_exists($href, $existingUrls)) {
                        $pageIdentifier = $pageData['slug'] ?? $pageUuid;
                        $messages[] = sprintf("Page '%s' contains broken link: %s", $pageIdentifier, $href);
                        $entityRefs[] = ['model' => 'Page', 'uuid' => $pageUuid];
                    }
                }
            }
        }

        if ($messages === []) {
            return new PublishCheckResult(
                identifier: $this->identifier(),
                label: $this->label(),
                severity: PublishCheckSeverity::Info,
            );
        }

        return new PublishCheckResult(
            identifier: $this->identifier(),
            label: $this->label(),
            severity: PublishCheckSeverity::Error,
            messages: $messages,
            entityRefs: $entityRefs,
        );
    }

    /** @return list<string> */
    private function extractInternalLinks(string $html): array
    {
        if ($html === '') {
            return [];
        }

        preg_match_all('/\bhref\s*=\s*"(\/[^"]*)"/i', $html, $matches);
        $fromDouble = $matches[1] ?? [];

        preg_match_all("/\\bhref\\s*=\\s*'(\\/[^']*)'/i", $html, $matches);
        $fromSingle = $matches[1] ?? [];

        return array_values(array_unique(array_merge($fromDouble, $fromSingle)));
    }
}
