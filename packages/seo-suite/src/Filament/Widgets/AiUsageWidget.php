<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Widgets;

use Capell\SeoSuite\Models\AIGenerationHistory;
use Filament\Widgets\Widget;

class AiUsageWidget extends Widget
{
    protected string $view = 'capell-seo-suite::filament.widgets.ai-usage';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

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
