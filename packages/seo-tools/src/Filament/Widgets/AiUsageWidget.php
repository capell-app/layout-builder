<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Widgets;

use Capell\SeoTools\Models\AIGenerationHistory;
use Filament\Widgets\Widget;

class AiUsageWidget extends Widget
{
    protected string $view = 'capell-seo-tools::filament.widgets.ai-usage';

    protected function getViewData(): array
    {
        $count = AIGenerationHistory::query()->count();
        $tokens = AIGenerationHistory::query()->sum('total_tokens');

        return [
            'generationCount' => $count,
            'totalTokens' => $tokens,
        ];
    }
}
