<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;
use InvalidArgumentException;

final readonly class LayoutBulkWidgetOperationData
{
    public function __construct(
        public string $type,
        public string $sourceWidgetKey,
        public ?string $targetWidgetKey = null,
        public ?string $sourceContainerKey = null,
        public ?string $targetContainerKey = null,
        public string $placement = 'after',
        public string $occurrenceMode = 'all',
        public ?int $sourceOccurrenceNumber = null,
        public string $removeWidgetAssetMode = 'warn',
    ) {
        if (LayoutBulkWidgetOperationType::tryFrom($type) === null) {
            throw new InvalidArgumentException(sprintf('Unsupported bulk layout operation [%s].', $type));
        }

        throw_if(trim($sourceWidgetKey) === '', InvalidArgumentException::class, 'A source widget key is required.');

        if (! in_array($placement, ['before', 'after', 'top', 'bottom'], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported placement [%s].', $placement));
        }

        if (! in_array($occurrenceMode, ['first', 'all', 'specific'], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported occurrence mode [%s].', $occurrenceMode));
        }

        throw_if($occurrenceMode === 'specific' && ($sourceOccurrenceNumber === null || $sourceOccurrenceNumber < 1), InvalidArgumentException::class, 'A positive source occurrence number is required for specific occurrence operations.');

        if (! in_array($removeWidgetAssetMode, ['warn', 'delete_page_scoped'], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported remove widget asset mode [%s].', $removeWidgetAssetMode));
        }
    }

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): self
    {
        return new self(
            type: self::stringValue($payload['type'] ?? $payload['operation_type'] ?? ''),
            sourceWidgetKey: self::stringValue($payload['source_widget_key'] ?? ''),
            targetWidgetKey: self::nullableString($payload['target_widget_key'] ?? null),
            sourceContainerKey: self::nullableString($payload['source_container_key'] ?? null),
            targetContainerKey: self::nullableString($payload['target_container_key'] ?? null),
            placement: self::stringValue($payload['placement'] ?? 'after'),
            occurrenceMode: self::stringValue($payload['occurrence_mode'] ?? 'all'),
            sourceOccurrenceNumber: self::nullableInteger($payload['source_occurrence_number'] ?? null),
            removeWidgetAssetMode: self::stringValue($payload['remove_widget_asset_mode'] ?? 'warn'),
        );
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'type' => $this->type,
            'source_widget_key' => $this->sourceWidgetKey,
            'target_widget_key' => $this->targetWidgetKey,
            'source_container_key' => $this->sourceContainerKey,
            'target_container_key' => $this->targetContainerKey,
            'placement' => $this->placement,
            'occurrence_mode' => $this->occurrenceMode,
            'source_occurrence_number' => $this->sourceOccurrenceNumber,
            'remove_widget_asset_mode' => $this->removeWidgetAssetMode,
        ];
    }

    public function typeEnum(): LayoutBulkWidgetOperationType
    {
        return LayoutBulkWidgetOperationType::from($this->type);
    }

    private static function stringValue(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? trim((string) $value) : '';
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = is_string($value) || is_numeric($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    private static function nullableInteger(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
