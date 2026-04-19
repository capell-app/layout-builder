<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Http;

use Capell\Themes\Core\Data\ThemeSettings;
use Symfony\Component\HttpFoundation\Response;

class ThemeTokensController
{
    public function __construct(private readonly ThemeSettings $settings) {}

    public function toCss(): string
    {
        $tokens = [
            '--color-primary' => $this->settings->primary_color,
            '--color-accent' => $this->settings->accent_color,
            '--font-headline' => $this->settings->headline_font,
            '--font-body' => $this->settings->body_font,
            '--spacing-preset' => $this->settings->spacing_preset,
        ];

        $lines = [];
        foreach ($tokens as $property => $value) {
            $lines[] = '  ' . $property . ': ' . $value . ';';
        }

        return ':root {' . "\n" . implode("\n", $lines) . "\n}";
    }

    public function render(): Response
    {
        return new Response(
            content: $this->toCss(),
            status: Response::HTTP_OK,
            headers: ['Content-Type' => 'text/css; charset=UTF-8'],
        );
    }
}
