<?php

declare(strict_types=1);

namespace Capell\Api\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

trait SanitizesPublicHtml
{
    private ?HtmlSanitizer $publicHtmlSanitizer = null;

    private function sanitizeHtmlValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->sanitizeHtml($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->sanitizeHtmlValue($item);
        }

        return $value;
    }

    private function sanitizeHtml(string $html): string
    {
        return $this->publicHtmlSanitizer()->sanitize($html);
    }

    private function publicHtmlSanitizer(): HtmlSanitizer
    {
        if ($this->publicHtmlSanitizer instanceof HtmlSanitizer) {
            return $this->publicHtmlSanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->withMaxInputLength(-1);

        foreach (['class'] as $attribute) {
            $config = $config->allowAttribute($attribute, '*');
        }

        return $this->publicHtmlSanitizer = new HtmlSanitizer($config);
    }
}
