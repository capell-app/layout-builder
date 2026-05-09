<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum AiDiscoveryCrawlerPolicyEnum: string implements HasLabel
{
    case SearchVisibleTrainingRestricted = 'search_visible_training_restricted';
    case Open = 'open';
    case Restrictive = 'restrictive';

    public function getLabel(): string
    {
        return __('capell-seo-suite::generic.ai_discovery_crawler_policy_' . $this->value);
    }
}
