<?php

declare(strict_types=1);

namespace Capell\Plugins\Enums;

enum Capability: string
{
    case WritesFiles = 'writes_files';
    case DbSchemaChanges = 'db_schema_changes';
    case HttpOutbound = 'http_outbound';
    case ReadsSecrets = 'reads_secrets';
    case AdminPages = 'admin_pages';
    case FrontendRoutes = 'frontend_routes';
    case QueueJobs = 'queue_jobs';
    case ModifiesCoreModels = 'modifies_core_models';

    public function acceptsParameter(): bool
    {
        return in_array($this, [self::WritesFiles, self::HttpOutbound], true);
    }
}
