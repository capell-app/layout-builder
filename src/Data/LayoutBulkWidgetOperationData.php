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
    ) {
        if (LayoutBulkWidgetOperationType::tryFrom($type) === null) {
            throw new InvalidArgumentException(sprintf('Unsupported bulk layout operation [%s].', $type));
        }

        if (trim($sourceWidgetKey) === '') {
            throw new InvalidArgumentException('A source widget key is required.');
        }

        if (! in_array($placement, ['before', 'after', 'top', 'bottom'], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported placement [%s].', $placement));
        }

        if (! in_array($occurrenceMode, ['first', 'all'], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported occurrence mode [%s].', $occurrenceMode));
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
        ];
    }

    public function typeEnum(): LayoutBulkWidgetOperationType
    {
        return LayoutBulkWidgetOperationType::from($this->type);
    }

    private static function stringValue(mixed $value): string
    {
        return trim((string) $value);
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
