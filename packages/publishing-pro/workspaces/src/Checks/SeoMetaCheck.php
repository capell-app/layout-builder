<?php

declare(strict_types=1);

namespace Capell\Workspaces\Checks;

use Capell\SeoTools\Contracts\SeoPublishReportProvider;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeoMetaCheck implements PublishCheck
{
    private const SEO_PUBLISH_REPORT_PROVIDER = SeoPublishReportProvider::class;

    public function identifier(): string
    {
        return 'seo-meta';
    }

    public function label(): string
    {
        return 'SEO Meta';
    }

    public function run(Workspace $workspace): PublishCheckResult
    {
        $seoToolsResult = $this->runSeoToolsProvider($workspace);

        if ($seoToolsResult instanceof PublishCheckResult) {
            return $seoToolsResult;
        }

        if (! Schema::hasColumn('pages', 'meta_title') || ! Schema::hasColumn('pages', 'meta_description')) {
            return new PublishCheckResult(
                identifier: $this->identifier(),
                label: $this->label(),
                severity: PublishCheckSeverity::Info,
            );
        }

        $pages = DB::table('pages')
            ->where('workspace_id', $workspace->id)
            ->whereRaw("(meta_title IS NULL OR meta_title = '' OR meta_description IS NULL OR meta_description = '')")
            ->select(['id', 'slug'])
            ->get();

        if ($pages->isEmpty()) {
            return new PublishCheckResult(
                identifier: $this->identifier(),
                label: $this->label(),
                severity: PublishCheckSeverity::Info,
            );
        }

        $messages = $pages->map(function (object $page): string {
            $identifier = $page->slug ?? (string) $page->id;

            return sprintf("Page '%s' is missing meta title or meta description.", $identifier);
        })->all();

        return new PublishCheckResult(
            identifier: $this->identifier(),
            label: $this->label(),
            severity: PublishCheckSeverity::Warn,
            messages: $messages,
        );
    }

    private function runSeoToolsProvider(Workspace $workspace): ?PublishCheckResult
    {
        if (! app()->bound(self::SEO_PUBLISH_REPORT_PROVIDER)) {
            return null;
        }

        $provider = app()->make(self::SEO_PUBLISH_REPORT_PROVIDER);

        if (! is_object($provider) || ! method_exists($provider, 'forWorkspace')) {
            return null;
        }

        $report = $provider->forWorkspace($workspace);
        $messages = [];
        $hasCriticalIssue = false;
        $hasWarnIssue = false;

        foreach ($report as $pageReport) {
            if (! is_array($pageReport)) {
                continue;
            }

            $pageLabel = $this->pageLabel($pageReport['page'] ?? null);
            $issues = $pageReport['issues'] ?? [];

            if (! is_array($issues)) {
                continue;
            }

            foreach ($issues as $issue) {
                if (! is_array($issue)) {
                    continue;
                }

                $severity = $this->issueSeverity($issue['severity'] ?? null);

                if ($severity === 'critical') {
                    $hasCriticalIssue = true;
                } elseif ($severity === 'warning' || $severity === 'notice') {
                    $hasWarnIssue = true;
                } else {
                    continue;
                }

                $message = is_string($issue['message'] ?? null)
                    ? trim($issue['message'])
                    : 'SEO issue detected.';

                $messages[] = sprintf("Page '%s': %s", $pageLabel, $message !== '' ? $message : 'SEO issue detected.');
            }
        }

        $severity = match (true) {
            $hasCriticalIssue => PublishCheckSeverity::Error,
            $hasWarnIssue => PublishCheckSeverity::Warn,
            default => PublishCheckSeverity::Info,
        };

        return new PublishCheckResult(
            identifier: $this->identifier(),
            label: $this->label(),
            severity: $severity,
            messages: $messages,
        );
    }

    private function pageLabel(mixed $page): string
    {
        if (! is_array($page)) {
            return 'unknown';
        }

        foreach (['label', 'slug', 'uuid', 'id'] as $key) {
            if (! array_key_exists($key, $page)) {
                continue;
            }

            if (! is_scalar($page[$key])) {
                continue;
            }

            $value = trim((string) $page[$key]);

            if ($value !== '') {
                return $value;
            }
        }

        return 'unknown';
    }

    private function issueSeverity(mixed $severity): ?string
    {
        if (is_object($severity) && property_exists($severity, 'value') && is_scalar($severity->value)) {
            return strtolower((string) $severity->value);
        }

        if (! is_scalar($severity)) {
            return null;
        }

        return strtolower((string) $severity);
    }
}
