<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

final class LayoutBuilderResidualFrontendContext
{
    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function setFrontendData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}
