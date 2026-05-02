<?php

declare(strict_types=1);

namespace Capell\Backup\Enums;

enum ImportSessionKind: string
{
    case PageImport = 'page-import';
    case SiteImport = 'site-import';
    case FullRestore = 'full-restore';
    case WordPressImport = 'wordpress-import';
    case SpreadsheetImport = 'spreadsheet-import';
}
