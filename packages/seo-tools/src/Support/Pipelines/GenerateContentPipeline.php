<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\Pipelines;

use Capell\SeoTools\Contracts\AiActionContextInterface;
use Capell\SeoTools\Models\AIGenerationHistory;
use Capell\SeoTools\Support\AiRateLimiter;
use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\PrismProvider;
use Capell\SeoTools\Support\PromptRepository;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

class GenerateContentPipeline
{
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly PrismProvider $provider,
        private readonly AiRateLimiter $rateLimiter,
    ) {}

    /**
     * @param  array{context: AiActionContextInterface, options: array{user_id?: int|null, current_title?: string|null, target_length?: int|null, refactor?: bool|null}, action: object}  $input
     */
    public function execute(array $input): string
    {
        $payload = resolve(Pipeline::class)
            ->send($input)
            ->through([
                fn (array $payload, callable $next): array => $this->validateInput($payload, $next),
                fn (array $payload, callable $next): array => $this->checkRateLimit($payload, $next),
                fn (array $payload, callable $next): array => $this->executeAiCall($payload, $next),
                fn (array $payload, callable $next): array => $this->parseResponse($payload, $next),
                fn (array $payload, callable $next): array => $this->recordGeneration($payload),
            ])
            ->thenReturn();

        return (string) ($payload['result'] ?? '');
    }

    private function validateInput(array $payload, callable $next): array
    {
        $context = $payload['context'] ?? null;
        throw_unless($context instanceof AiActionContextInterface, InvalidArgumentException::class, 'Missing AiActionContextInterface context');

        return $next($payload);
    }

    private function checkRateLimit(array $payload, callable $next): array
    {
        $identifier = (string) ($payload['options']['user_id'] ?? 'global');
        $this->rateLimiter->checkLimit($identifier, 'content_generation');

        return $next($payload);
    }

    private function executeAiCall(array $payload, callable $next): array
    {
        /** @var AiActionContextInterface $context */
        $context = $payload['context'];
        $options = $payload['options'] ?? [];
        $prompt = $this->prompts->get('content_generation');

        $userMessage = strtr((string) ($prompt['user_template'] ?? ''), [
            '{{current_title}}' => (string) ($options['current_title'] ?? ''),
            '{{keywords}}' => $context->getKeywords() ?? '',
            '{{content}}' => $context->getContent() ?? '',
            '{{target_length}}' => ($options['target_length'] ?? null) !== null ? (string) $options['target_length'] : 'auto',
            '{{refactor}}' => ((bool) ($options['refactor'] ?? true)) ? 'yes' : 'no',
        ]);

        $messages = [
            ['role' => 'system', 'content' => (string) ($prompt['system'] ?? '')],
            ['role' => 'user', 'content' => $userMessage],
        ];

        $params = [
            'model' => (string) ($prompt['model'] ?? config('capell-seo-tools.prism.model')),
            'messages' => $messages,
            'max_tokens' => config('capell-seo-tools.prism.max_tokens', 4096),
            'temperature' => 0.7,
        ];

        $response = $this->provider->chat($params);
        $payload['ai_response'] = $response;
        $payload['ai_messages'] = $messages;
        $payload['ai_params'] = $params;

        return $next($payload);
    }

    private function parseResponse(array $payload, callable $next): array
    {
        /** @var AiResponse $response */
        $response = $payload['ai_response'];
        $raw = trim($response->content);

        // Sanitize final HTML/text string
        $payload['result'] = $this->sanitizeHtml($raw);

        return $next($payload);
    }

    private function recordGeneration(array $payload): array
    {
        /** @var AiResponse $response */
        $response = $payload['ai_response'] ?? null;
        /** @var AiActionContextInterface $context */
        $context = $payload['context'];
        if ($response !== null) {
            AIGenerationHistory::query()->create([
                'action' => 'GeneratorPageContentAction',
                'model' => $response->model,
                'input' => $context->getContent(),
                'output' => (string) ($payload['result'] ?? ''),
                'prompt_tokens' => (int) ($response->metadata['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($response->metadata['completion_tokens'] ?? 0),
                'total_tokens' => $response->tokensUsed,
                'duration' => $response->duration,
                'pageable_id' => $context->getPageId(),
                'pageable_type' => $context->getPageType(),
                'language_id' => $context->getLanguageId(),
                'metadata' => array_merge($response->metadata, [
                    'ai_messages' => $payload['ai_messages'] ?? null,
                    'ai_params' => $payload['ai_params'] ?? null,
                ]),
            ]);
        }

        return $payload;
    }

    /**
     * Basic sanitizer to keep AI output safe and user-friendly.
     * - strips script/style blocks
     * - removes inline event handlers (on*)
     * - neutralizes javascript: URLs
     * - removes external absolute links, preserves relative links
     */
    private function sanitizeHtml(string $html): string
    {
        if (class_exists(DOMDocument::class)) {
            return $this->sanitizeHtmlWithDom($html);
        }

        return $this->sanitizeHtmlWithPatterns($html);
    }

    private function sanitizeHtmlWithDom(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorHandling = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="capell-sanitizer-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);

        $root = $document->getElementById('capell-sanitizer-root');

        if (! $root instanceof DOMElement) {
            return $this->sanitizeHtmlWithPatterns($html);
        }

        $this->sanitizeChildNodes($root, $document);

        $output = '';
        foreach ($root->childNodes as $childNode) {
            $output .= $document->saveHTML($childNode);
        }

        return $output;
    }

    private function sanitizeChildNodes(DOMNode $node, DOMDocument $document): void
    {
        $childNode = $node->firstChild;

        while ($childNode instanceof DOMNode) {
            $nextNode = $childNode->nextSibling;

            if ($childNode instanceof DOMElement) {
                $tagName = strtolower($childNode->tagName);

                if (in_array($tagName, $this->blockedTags(), true)) {
                    $node->removeChild($childNode);
                    $childNode = $nextNode;

                    continue;
                }

                if (! in_array($tagName, $this->allowedTags(), true)) {
                    $this->sanitizeChildNodes($childNode, $document);
                    $this->unwrapElement($childNode);
                    $childNode = $nextNode;

                    continue;
                }

                if ($tagName === 'a' && ! $this->isSafeRelativeHref($childNode->getAttribute('href'))) {
                    $node->replaceChild($this->textSpan($document, $childNode->textContent), $childNode);
                    $childNode = $nextNode;

                    continue;
                }

                $this->sanitizeAttributes($childNode);
                $this->sanitizeChildNodes($childNode, $document);
            }

            $childNode = $nextNode;
        }
    }

    private function sanitizeAttributes(DOMElement $element): void
    {
        $tagName = strtolower($element->tagName);
        $allowedAttributes = $this->allowedAttributesForTag($tagName);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $attributeName = strtolower($attribute->nodeName);
            $attributeValue = $attribute->nodeValue ?? '';

            if (str_starts_with($attributeName, 'on') || ! in_array($attributeName, $allowedAttributes, true)) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if ($attributeName === 'src' && ! $this->isSafeImageSource($attributeValue)) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    private function unwrapElement(DOMElement $element): void
    {
        $parentNode = $element->parentNode;

        if (! $parentNode instanceof DOMNode) {
            return;
        }

        while ($element->firstChild instanceof DOMNode) {
            $parentNode->insertBefore($element->firstChild, $element);
        }

        $parentNode->removeChild($element);
    }

    private function textSpan(DOMDocument $document, string $text): DOMElement
    {
        $span = $document->createElement('span');
        $span->appendChild($document->createTextNode($text));

        return $span;
    }

    /** @return list<string> */
    private function blockedTags(): array
    {
        return ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'];
    }

    /** @return list<string> */
    private function allowedTags(): array
    {
        return [
            'a',
            'b',
            'blockquote',
            'br',
            'code',
            'div',
            'em',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'i',
            'img',
            'li',
            'ol',
            'p',
            'pre',
            'span',
            'strong',
            'u',
            'ul',
        ];
    }

    /** @return list<string> */
    private function allowedAttributesForTag(string $tagName): array
    {
        return match ($tagName) {
            'a' => ['href', 'title'],
            'img' => ['src', 'alt', 'title', 'width', 'height'],
            default => [],
        };
    }

    private function isSafeRelativeHref(string $href): bool
    {
        $normalizedHref = $this->normalizeUrl($href);

        return str_starts_with($normalizedHref, '/')
            || str_starts_with($normalizedHref, './')
            || str_starts_with($normalizedHref, '../')
            || str_starts_with($normalizedHref, '#');
    }

    private function isSafeImageSource(string $source): bool
    {
        $normalizedSource = $this->normalizeUrl($source);

        if ($normalizedSource === '') {
            return false;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $normalizedSource) !== 1) {
            return ! str_starts_with($normalizedSource, '//');
        }

        return preg_match('#^https?://#i', $normalizedSource) === 1;
    }

    private function normalizeUrl(string $value): string
    {
        $decoded = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return (string) preg_replace('/[\x00-\x20]+/', '', $decoded);
    }

    private function sanitizeHtmlWithPatterns(string $html): string
    {
        // Remove <script> and <style> blocks
        $clean = preg_replace('#<\s*(script|style|iframe|object|embed|svg|math)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? $html;

        // Remove <iframe> blocks entirely
        $clean = preg_replace('#<\s*iframe[^>]*>.*?<\s*/\s*iframe\s*>#is', '', $clean) ?? $clean;

        // Remove inline event handlers like onclick, onerror, onload
        $clean = preg_replace('/\son[a-z0-9:_-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;

        // Neutralize javascript: URLs
        $clean = preg_replace('/\shref\s*=\s*(?:"\s*javascript:[^"]*"|\'\s*javascript:[^\']*\'|\s*javascript:[^\s>]+)/i', ' href="#"', $clean) ?? $clean;

        // Convert external absolute links to plain text or '#'
        // Preserve relative links (/, ./, ../) and anchors (#...)
        $clean = preg_replace_callback('#<a\s+[^>]*href\s*=\s*(["\'])([^"\']+)\1[^>]*>(.*?)</a>#is', function (array $matches): string {
            $href = $matches[2];
            $text = trim(strip_tags($matches[3]));

            $isRelative = str_starts_with($href, '/') || str_starts_with($href, './') || str_starts_with($href, '../') || str_starts_with($href, '#');
            $isAbsolute = preg_match('#^https?://#i', $href) === 1;

            if ($isRelative) {
                // keep relative links, but remove target and rel attributes
                $safeAnchor = preg_replace(['/\s+target=(["\']).*?\1/i', '/\s+rel=(["\']).*?\1/i'], ['', ''], $matches[0]) ?? $matches[0];

                return $safeAnchor;
            }

            if ($isAbsolute) {
                // Replace external links with a non-clickable span containing text
                return $text !== '' ? '<span>' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>' : '<span></span>';
            }

            // Unknown scheme: neutralize
            return '<span>' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
        }, $clean) ?? $clean;

        return $clean;
    }
}
