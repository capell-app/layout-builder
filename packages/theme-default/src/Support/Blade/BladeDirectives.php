<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Support\Blade;

use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;

class BladeDirectives
{
    public static function register(): void
    {
        Blade::directive('capellBuffer', static fn (?string $expression): string => self::compileCapellCapture((string) $expression));
        Blade::directive('endcapellBuffer', static fn (): string => self::compileEndCapellCapture());
    }

    private static function compileCapellCapture(string $expression): string
    {
        ['target' => $target, 'arguments' => $arguments] = self::parseCaptureExpression($expression);

        return "<?php {$target} = (static function (array \$__capellBufferScope) {
                return static function ({$arguments}) use (\$__capellBufferScope): \\Illuminate\\Support\\HtmlString {
                    extract(\$__capellBufferScope, EXTR_SKIP);
                    ob_start();
                    \$__capellBufferLevel = ob_get_level();
                    try {
            ?>";
    }

    private static function compileEndCapellCapture(): string
    {
        return '<?php
                    while (ob_get_level() > $__capellBufferLevel) {
                        ob_end_clean();
                    }

                    return new \\Illuminate\\Support\\HtmlString((string) ob_get_clean());
                } catch (\\Throwable $__capellBufferException) {
                    while (ob_get_level() >= $__capellBufferLevel) {
                        ob_end_clean();
                    }

                    throw $__capellBufferException;
                }
            };
        })(get_defined_vars());
        ?>';
    }

    /**
     * @return array{target: string, arguments: string}
     */
    private static function parseCaptureExpression(string $expression): array
    {
        $trimmedExpression = trim($expression);

        throw_if($trimmedExpression === '', InvalidArgumentException::class, 'The @capellBuffer directive requires a target variable.');

        $commaPosition = self::findFirstTopLevelComma($trimmedExpression);

        if ($commaPosition === null) {
            return [
                'target' => $trimmedExpression,
                'arguments' => '',
            ];
        }

        return [
            'target' => trim(substr($trimmedExpression, 0, $commaPosition)),
            'arguments' => trim(substr($trimmedExpression, $commaPosition + 1)),
        ];
    }

    private static function findFirstTopLevelComma(string $expression): ?int
    {
        $length = strlen($expression);
        $parenthesesDepth = 0;
        $bracketsDepth = 0;
        $bracesDepth = 0;
        $insideSingleQuote = false;
        $insideDoubleQuote = false;
        $isEscaped = false;

        for ($index = 0; $index < $length; $index++) {
            $character = $expression[$index];

            if ($isEscaped) {
                $isEscaped = false;

                continue;
            }

            if ($character === '\\') {
                $isEscaped = true;

                continue;
            }

            if ($insideSingleQuote) {
                if ($character === "'") {
                    $insideSingleQuote = false;
                }

                continue;
            }

            if ($insideDoubleQuote) {
                if ($character === '"') {
                    $insideDoubleQuote = false;
                }

                continue;
            }

            if ($character === "'") {
                $insideSingleQuote = true;

                continue;
            }

            if ($character === '"') {
                $insideDoubleQuote = true;

                continue;
            }

            if ($character === '(') {
                $parenthesesDepth++;

                continue;
            }

            if ($character === ')') {
                $parenthesesDepth = max(0, $parenthesesDepth - 1);

                continue;
            }

            if ($character === '[') {
                $bracketsDepth++;

                continue;
            }

            if ($character === ']') {
                $bracketsDepth = max(0, $bracketsDepth - 1);

                continue;
            }

            if ($character === '{') {
                $bracesDepth++;

                continue;
            }

            if ($character === '}') {
                $bracesDepth = max(0, $bracesDepth - 1);

                continue;
            }

            if ($character === ',' && $parenthesesDepth === 0 && $bracketsDepth === 0 && $bracesDepth === 0) {
                return $index;
            }
        }

        return null;
    }
}
