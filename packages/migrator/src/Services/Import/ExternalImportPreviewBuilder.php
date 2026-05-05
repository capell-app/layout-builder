<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import;

use Capell\Migrator\Data\ExternalImportPreview;
use Capell\Migrator\Data\ExternalImportReadResult;

final readonly class ExternalImportPreviewBuilder
{
    public function __construct(private FieldMapper $fieldMapper = new FieldMapper) {}

    /**
     * @param  array<string, string>  $mapping
     */
    public function build(ExternalImportReadResult $source, array $mapping = [], ?string $target = null): ExternalImportPreview
    {
        $target ??= $source->suggestedTarget;
        $rows = [];
        $errors = [];

        foreach ($source->rows as $index => $row) {
            $mapped = $this->fieldMapper->map($row, $mapping, $target);
            $name = $mapped['name'] ?? null;

            if (! is_string($name) || trim($name) === '') {
                $errors[] = sprintf('Row %d is missing a mapped name/title field.', $index + 1);
            }

            $rows[] = [
                'row' => $index + 1,
                'action' => 'create',
                'attributes' => $mapped,
            ];
        }

        return new ExternalImportPreview(
            target: $target,
            creates: count($rows),
            skips: 0,
            rows: $rows,
            errors: $errors,
        );
    }
}
