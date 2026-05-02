<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Registry;

use Illuminate\Database\Eloquent\Model;

class NavigableResult
{
    public function __construct(
        public readonly string $label,
        public readonly string $url,
    ) {}

    public static function fromModel(Model $model): self
    {
        return new self(
            label: method_exists($model, 'getNavigationLabel') ? $model->getNavigationLabel() : (string) $model->getKey(),
            url: method_exists($model, 'getNavigationUrl') ? $model->getNavigationUrl() : '',
        );
    }
}
