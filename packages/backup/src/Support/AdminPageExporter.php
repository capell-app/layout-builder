<?php

declare(strict_types=1);

namespace Capell\Backup\Support;

use Capell\Admin\Contracts\Backup\PageExporter;
use Capell\Backup\Data\ExportOptions;
use Capell\Backup\Services\Export\PageExportService;

class AdminPageExporter implements PageExporter
{
    public function __construct(
        private readonly PageExportService $pageExportService,
    ) {}

    public function exportPages(array $pageIds, array $options): string
    {
        return $this->pageExportService->exportPages($pageIds, $this->toExportOptions($options));
    }

    public function exportSites(array $siteIds, array $options): string
    {
        return $this->pageExportService->exportSites($siteIds, $this->toExportOptions($options));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function toExportOptions(array $options): ExportOptions
    {
        return new ExportOptions(
            includeTranslations: (bool) ($options['include_translations'] ?? true),
            includeMedia: (bool) ($options['include_media'] ?? true),
            includeSharedRelations: (bool) ($options['include_shared_relations'] ?? true),
            includeAllContexts: (bool) ($options['include_all_contexts'] ?? false),
            note: $options['note'] ?? null,
        );
    }
}
