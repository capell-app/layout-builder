<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Data;

use Capell\ThemeStudio\Core\Contracts\ThemeSection;
use Spatie\LaravelData\Data;

class ThemePageData extends Data
{
    /**
     * @param  array<int, ThemeSection>  $sections
     */
    public function __construct(
        public string $title,
        public BrandProfileData $brand,
        public array $sections = [],
        public ?NavigationData $navigation = null,
        public ?FooterData $footer = null,
    ) {}

    /**
     * @return array<int, ThemeSection>
     */
    public function allSections(): array
    {
        return array_values(array_filter([
            $this->navigation,
            ...$this->sections,
            $this->footer,
        ]));
    }
}
