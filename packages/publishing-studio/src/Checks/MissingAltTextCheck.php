<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Checks;

use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MissingAltTextCheck implements PublishCheck
{
    public function identifier(): string
    {
        return 'missing-alt-text';
    }

    public function label(): string
    {
        return 'Missing Alt Text';
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
            $offendingCount = $this->countImagesWithoutAlt((string) $page->body);

            if ($offendingCount > 0) {
                $identifier = $page->slug ?? $page->uuid;
                $messages[] = sprintf("Page '%s' has %d image(s) missing alt text.", $identifier, $offendingCount);
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

    private function countImagesWithoutAlt(string $html): int
    {
        if ($html === '') {
            return 0;
        }

        $count = 0;

        // Match <img ...> tags
        preg_match_all('/<img\b[^>]*>/i', $html, $imgMatches);

        foreach ($imgMatches[0] as $imgTag) {
            // Check if alt attribute is absent or empty
            if (! preg_match('/\balt\s*=\s*"[^"]+"/i', $imgTag) && ! preg_match('/\balt\s*=\s*\'[^\']+\'/i', $imgTag)) {
                $count++;
            }
        }

        return $count;
    }
}
