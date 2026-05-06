<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Enums;

enum PackageType: string
{
    case PageExport = 'page-export';
    case SiteExport = 'site-export';
}
