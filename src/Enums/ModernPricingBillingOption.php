<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernPricingBillingOption: string implements HasLabel
{
    case Monthly = 'monthly';
    case Annual = 'annual';
    case Both = 'both';

    public function getLabel(): string
    {
        return match ($this) {
            self::Monthly => __('capell-layout-builder::widgets.modern.pricing_table.billing_monthly'),
            self::Annual => __('capell-layout-builder::widgets.modern.pricing_table.billing_annual'),
            self::Both => __('capell-layout-builder::widgets.modern.pricing_table.billing_both'),
        };
    }
}
