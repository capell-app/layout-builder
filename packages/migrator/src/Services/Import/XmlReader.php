<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import;

use Capell\Migrator\Contracts\ImportSourceReader;
use Capell\Migrator\Data\ExternalImportReadResult;
use RuntimeException;
use SimpleXMLElement;

final class XmlReader implements ImportSourceReader
{
    public function supports(string $extension): bool
    {
        return strtolower($extension) === 'xml';
    }

    public function read(string $path): ExternalImportReadResult
    {
        if (! is_readable($path)) {
            throw new RuntimeException(sprintf('XML import source [%s] is not readable.', $path));
        }

        $xml = simplexml_load_file($path, SimpleXMLElement::class, LIBXML_NONET);
        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException(sprintf('XML import source [%s] could not be parsed.', $path));
        }

        $items = $this->itemElements($xml);
        $rows = array_map(fn (SimpleXMLElement $item): array => $this->flatten($item), $items);
        $columns = array_values(array_unique(array_merge(...array_map(array_keys(...), $rows !== [] ? $rows : [[]]))));

        return new ExternalImportReadResult(
            sourceType: 'xml',
            columns: $columns,
            rows: $rows,
            metadata: [
                'filename' => basename($path),
                'root' => $xml->getName(),
            ],
        );
    }

    /**
     * @return list<SimpleXMLElement>
     */
    private function itemElements(SimpleXMLElement $xml): array
    {
        $children = [];
        foreach ($xml->children() as $child) {
            $children[] = $child;
        }

        if ($children === []) {
            return [$xml];
        }

        $firstChildName = $children[0]->getName();
        $sameNamedChildren = array_values(array_filter(
            $children,
            static fn (SimpleXMLElement $child): bool => $child->getName() === $firstChildName,
        ));

        return count($sameNamedChildren) > 1 ? $sameNamedChildren : $children;
    }

    /**
     * @return array<string, mixed>
     */
    private function flatten(SimpleXMLElement $element, string $prefix = ''): array
    {
        $row = [];

        foreach ($element->children() as $child) {
            $key = $prefix === '' ? $child->getName() : $prefix . '.' . $child->getName();

            if ($child->children()->count() > 0) {
                $row = array_merge($row, $this->flatten($child, $key));

                continue;
            }

            $row[$key] = trim((string) $child);
        }

        if ($row === []) {
            $row[$prefix === '' ? $element->getName() : $prefix] = trim((string) $element);
        }

        return $row;
    }
}
