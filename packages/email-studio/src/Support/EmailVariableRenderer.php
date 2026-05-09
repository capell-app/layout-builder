<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Support;

use Capell\EmailStudio\Exceptions\EmailTemplateRenderingException;
use Stringable;

class EmailVariableRenderer
{
    private const VARIABLE_PATTERN = '/{{\s*([A-Za-z_][A-Za-z0-9_]*)\s*}}/';

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<int, string>  $declaredVariables
     */
    public function renderHtml(string $template, array $variables, array $declaredVariables, bool $preview): string
    {
        return $this->render($template, $variables, $declaredVariables, $preview, escape: true);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<int, string>  $declaredVariables
     */
    public function renderText(?string $template, array $variables, array $declaredVariables, bool $preview): ?string
    {
        if ($template === null) {
            return null;
        }

        return $this->render($template, $variables, $declaredVariables, $preview, escape: false);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<int, string>  $declaredVariables
     */
    public function renderEscapedText(?string $template, array $variables, array $declaredVariables, bool $preview): ?string
    {
        if ($template === null) {
            return null;
        }

        return $this->render($template, $variables, $declaredVariables, $preview, escape: true);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<int, string>  $declaredVariables
     */
    private function render(
        string $template,
        array $variables,
        array $declaredVariables,
        bool $preview,
        bool $escape,
    ): string {
        $missingVariables = $this->missingVariables($template, $variables, $declaredVariables);

        if ($missingVariables !== [] && ! $preview) {
            throw EmailTemplateRenderingException::missingVariables($missingVariables);
        }

        return (string) preg_replace_callback(
            self::VARIABLE_PATTERN,
            function (array $matches) use ($variables, $declaredVariables, $escape): string {
                $variableName = (string) $matches[1];

                if (! in_array($variableName, $declaredVariables, true) || ! array_key_exists($variableName, $variables)) {
                    return (string) $matches[0];
                }

                $value = $this->stringValue($variables[$variableName]);

                return $escape ? e($value) : $value;
            },
            $template,
        );
    }

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<int, string>  $declaredVariables
     * @return array<int, string>
     */
    private function missingVariables(string $template, array $variables, array $declaredVariables): array
    {
        preg_match_all(self::VARIABLE_PATTERN, $template, $matches);

        $missingVariables = [];

        foreach ($matches[1] ?? [] as $variableName) {
            if (! is_string($variableName)) {
                continue;
            }

            if (! in_array($variableName, $declaredVariables, true) || ! array_key_exists($variableName, $variables)) {
                $missingVariables[] = $variableName;
            }
        }

        return array_values(array_unique($missingVariables));
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return '';
    }
}
