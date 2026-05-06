<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Checks;

use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccessibilityCheck implements PublishCheck
{
    public function identifier(): string
    {
        return 'accessibility';
    }

    public function label(): string
    {
        return 'Accessibility';
    }

    public function run(Workspace $workspace): PublishCheckResult
    {
        if (! Schema::hasColumn('pages', 'body')) {
            return new PublishCheckResult(
                identifier: $this->identifier(),
                label: $this->label(),
                severity: PublishCheckSeverity::Info,
            );
        }

        $pages = DB::table('pages')
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('body')
            ->select(['uuid', 'slug', 'body'])
            ->get();

        $messages = [];
        $entityRefs = [];

        foreach ($pages as $page) {
            $pageIssues = $this->detectIssues((string) $page->body);

            if ($pageIssues !== []) {
                $pageIdentifier = $page->slug ?? $page->uuid;

                foreach ($pageIssues as $issue) {
                    $messages[] = sprintf("Page '%s': %s", $pageIdentifier, $issue);
                }

                $entityRefs[] = ['model' => 'Page', 'uuid' => $page->uuid];
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
            severity: PublishCheckSeverity::Warn,
            messages: $messages,
            entityRefs: $entityRefs,
        );
    }

    /** @return list<string> */
    private function detectIssues(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $issues = [];

        // Detect empty anchor tags (no text content)
        preg_match_all('/<a\b[^>]*>([\s]*)<\/a>/i', $html, $anchorMatches);
        $emptyAnchorCount = count($anchorMatches[0]);

        if ($emptyAnchorCount > 0) {
            $issues[] = sprintf('Found %d anchor(s) with no text content.', $emptyAnchorCount);
        }

        // Detect images used as links without alt text: <a ...><img ...></a>
        // where the img has no meaningful alt attribute
        preg_match_all('/<a\b[^>]*>\s*(<img\b[^>]*>)\s*<\/a>/i', $html, $linkedImgMatches);

        $missingAltLinkCount = 0;
        foreach ($linkedImgMatches[1] as $imgTag) {
            if (! preg_match('/\balt\s*=\s*"[^"]+"/i', $imgTag) && ! preg_match('/\balt\s*=\s*\'[^\']+\'/i', $imgTag)) {
                $missingAltLinkCount++;
            }
        }

        if ($missingAltLinkCount > 0) {
            $issues[] = sprintf('Found %d image(s) used as links without alt text.', $missingAltLinkCount);
        }

        return $issues;
    }
}
