<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Data;

final class EditableRegionData
{
    public function __construct(
        public string $id,
        public string $label,
        public string $type,
        public string $selector,
        public string $editUrl,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'selector' => $this->selector,
            'edit_url' => $this->editUrl,
        ];
    }
}
