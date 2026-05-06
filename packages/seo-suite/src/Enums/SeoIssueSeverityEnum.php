<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum SeoIssueSeverityEnum: string implements HasLabel
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Notice = 'notice';
    case Passed = 'passed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Critical => __('capell-seo-suite::generic.seo_severity_critical'),
            self::Warning => __('capell-seo-suite::generic.seo_severity_warning'),
            self::Notice => __('capell-seo-suite::generic.seo_severity_notice'),
            self::Passed => __('capell-seo-suite::generic.seo_severity_passed'),
        };
    }

    public function penalty(): int
    {
        return match ($this) {
            self::Critical => 25,
            self::Warning => 10,
            self::Notice => 3,
            self::Passed => 0,
        };
    }
}
