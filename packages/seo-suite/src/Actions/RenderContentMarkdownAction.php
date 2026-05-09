<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use DOMDocument;
use DOMElement;
use DOMNode;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static string run(string|array|null $content)
 */
final class RenderContentMarkdownAction
{
    use AsAction;

    public function handle(string|array|null $content): string
    {
        if ($content === null) {
            return '';
        }

        if (is_array($content)) {
            return $this->renderArray($content);
        }

        return $this->renderString($content);
    }

    /**
     * @param  array<mixed>  $content
     */
    private function renderArray(array $content): string
    {
        $parts = [];

        foreach ($content as $value) {
            if (is_string($value) || is_array($value)) {
                $markdown = $this->handle($value);

                if ($markdown !== '') {
                    $parts[] = $markdown;
                }
            }
        }

        return implode("\n\n", $parts);
    }

    private function renderString(string $content): string
    {
        $content = trim($content);

        if ($content === '') {
            return '';
        }

        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $this->renderArray($decoded);
        }

        if (! str_contains($content, '<')) {
            return trim($content);
        }

        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>' . $content . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMNode) {
            return trim(strip_tags($content));
        }

        return $this->cleanMarkdown($this->renderChildren($body));
    }

    private function renderNode(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return preg_replace('/\s+/u', ' ', $node->textContent) ?? $node->textContent;
        }

        if (! $node instanceof DOMElement) {
            return $this->renderChildren($node);
        }

        $tagName = strtolower($node->tagName);

        if (preg_match('/^h([1-6])$/', $tagName, $matches) === 1) {
            return "\n\n" . str_repeat('#', (int) $matches[1]) . ' ' . trim($this->plainText($node)) . "\n\n";
        }

        return match ($tagName) {
            'p', 'div', 'section', 'article' => "\n\n" . trim($this->renderChildren($node)) . "\n\n",
            'br' => "\n",
            'ul' => "\n" . $this->renderList($node, ordered: false) . "\n",
            'ol' => "\n" . $this->renderList($node, ordered: true) . "\n",
            'li' => trim($this->renderChildren($node)),
            'a' => $this->renderLink($node),
            default => $this->renderChildren($node),
        };
    }

    private function renderChildren(DOMNode $node): string
    {
        $markdown = '';

        foreach ($node->childNodes as $childNode) {
            $markdown .= $this->renderNode($childNode);
        }

        return $markdown;
    }

    private function renderList(DOMElement $node, bool $ordered): string
    {
        $lines = [];
        $index = 1;

        foreach ($node->childNodes as $childNode) {
            if (! $childNode instanceof DOMElement) {
                continue;
            }

            if (strtolower($childNode->tagName) !== 'li') {
                continue;
            }

            $prefix = $ordered ? $index . '. ' : '- ';
            $lines[] = $prefix . trim($this->renderChildren($childNode));
            $index++;
        }

        return implode("\n", $lines);
    }

    private function renderLink(DOMElement $node): string
    {
        $text = trim($this->plainText($node));
        $href = trim($node->getAttribute('href'));

        if ($text === '') {
            return '';
        }

        if ($href === '') {
            return $text;
        }

        return sprintf('[%s](%s)', $text, $href);
    }

    private function plainText(DOMNode $node): string
    {
        return trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? $node->textContent);
    }

    private function cleanMarkdown(string $markdown): string
    {
        $markdown = preg_replace("/[ \t]+\n/u", "\n", $markdown) ?? $markdown;
        $markdown = preg_replace("/\n{3,}/u", "\n\n", $markdown) ?? $markdown;

        return trim($markdown);
    }
}
