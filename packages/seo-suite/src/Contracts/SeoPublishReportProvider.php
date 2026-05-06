<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Contracts;

use Capell\PublishingStudio\Models\Workspace;

interface SeoPublishReportProvider
{
    /**
     * @return array<int, array{
     *     page: array{id: int|string|null, uuid?: string|null, label: string},
     *     issues: array<int, array{key: string, severity: string, message: string}>
     * }>
     */
    public function forWorkspace(Workspace $workspace): array;
}
