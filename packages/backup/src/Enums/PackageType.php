<?php

declare(strict_types=1);

namespace Capell\Backup\Enums;

enum PackageType: string
{
    case PageExport = 'page-export';
    case SiteExport = 'site-export';
    case FullBackup = 'full-backup';
}
