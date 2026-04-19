<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Accessibility;

class ContrastChecker
{
    public function ratio(string $hex1, string $hex2): float
    {
        $color1 = $this->parseHex($hex1);
        $color2 = $this->parseHex($hex2);

        $luminance1 = $this->luminance($color1['r'], $color1['g'], $color1['b']);
        $luminance2 = $this->luminance($color2['r'], $color2['g'], $color2['b']);

        $lighter = max($luminance1, $luminance2);
        $darker = min($luminance1, $luminance2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public function meetsAA(float $ratio): bool
    {
        return $ratio >= 4.5;
    }

    public function meetsAALarge(float $ratio): bool
    {
        return $ratio >= 3.0;
    }

    public function meetsAAA(float $ratio): bool
    {
        return $ratio >= 7.0;
    }

    /**
     * @return array{r: int, g: int, b: int}
     */
    public function parseHex(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            'r' => (int) hexdec(substr($hex, 0, 2)),
            'g' => (int) hexdec(substr($hex, 2, 2)),
            'b' => (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public function luminance(int $red, int $green, int $blue): float
    {
        $linearRed = $this->linearise($red);
        $linearGreen = $this->linearise($green);
        $linearBlue = $this->linearise($blue);

        return 0.2126 * $linearRed + 0.7152 * $linearGreen + 0.0722 * $linearBlue;
    }

    private function linearise(int $channel): float
    {
        $value = $channel / 255;

        if ($value <= 0.04045) {
            return $value / 12.92;
        }

        return (($value + 0.055) / 1.055) ** 2.4;
    }
}
