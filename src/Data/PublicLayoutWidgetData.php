<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

class PublicLayoutWidgetData extends Data
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $key,
        public int $occurrence,
        public ?string $type,
        public array $data,
        public ?string $html = null,
    ) {}
}
