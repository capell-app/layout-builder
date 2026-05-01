<?php

declare(strict_types=1);

namespace Capell\Address\View\Components;

use Capell\Address\Support\FlagIconRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FlagIcon extends Component
{
    public ?string $asset;

    public string $fallbackLabel;

    public function __construct(
        public ?string $flag = null,
        public ?string $label = null,
        public string $style = '4x3',
    ) {
        $renderer = resolve(FlagIconRenderer::class);

        $this->asset = $renderer->assetPath($this->flag, $this->style);
        $this->fallbackLabel = $renderer->fallbackLabel($this->flag, $this->label, $this->style);
    }

    public function render(): View
    {
        return view('capell-address::components.flag-icon');
    }
}
