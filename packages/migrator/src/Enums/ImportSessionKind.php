<?php

declare(strict_types=1);

namespace Capell\Migrator\Enums;

enum ImportSessionKind: string
{
    case PageImport = 'page-import';
    case SiteImport = 'site-import';
    case WordPressImport = 'wordpress-import';
    case SpreadsheetImport = 'spreadsheet-import';
}
