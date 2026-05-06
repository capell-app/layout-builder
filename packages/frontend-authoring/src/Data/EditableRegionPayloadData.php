<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Data;

final class EditableRegionPayloadData
{
    public function __construct(
        public string $model,
        public int $recordKey,
        public string $field,
        public string $label,
        public string $type,
        public string $selector,
        public string $currentUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            model: (string) $payload['model'],
            recordKey: (int) $payload['recordKey'],
            field: (string) $payload['field'],
            label: (string) $payload['label'],
            type: (string) $payload['type'],
            selector: (string) $payload['selector'],
            currentUrl: (string) $payload['currentUrl'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'recordKey' => $this->recordKey,
            'field' => $this->field,
            'label' => $this->label,
            'type' => $this->type,
            'selector' => $this->selector,
            'currentUrl' => $this->currentUrl,
        ];
    }
}
