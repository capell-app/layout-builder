<?php

declare(strict_types=1);

namespace Capell\SeoTools\Enums;

enum SeoSnapshotStatusEnum: string
{
    case Unknown = 'unknown';
    case Missing = 'missing';
    case Warning = 'warning';
    case Passed = 'passed';
    case Declining = 'declining';
}
