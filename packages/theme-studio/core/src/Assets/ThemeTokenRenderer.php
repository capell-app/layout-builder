<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Assets;

use Capell\ThemeStudio\Core\Data\BrandProfileData;

class ThemeTokenRenderer
{
    public function css(BrandProfileData $brand): string
    {
        $lines = [];

        foreach ($brand->tokens() as $token => $value) {
            $lines[] = '    ' . $token . ': ' . $value . ';';
        }

        return ":root {\n" . implode("\n", $lines) . "\n}\n";
    }
}
