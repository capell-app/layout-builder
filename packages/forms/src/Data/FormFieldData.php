<?php

declare(strict_types=1);

namespace Capell\Forms\Data;

use Capell\Forms\Enums\FormFieldType;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class FormFieldData extends Data
{
    /**
     * @param  array<string, string>  $options
     * @param  array<int, string>  $validationRules
     */
    public function __construct(
        public string $key,
        public string $label,
        public FormFieldType $type = FormFieldType::Text,
        public bool $required = false,
        public ?string $placeholder = null,
        public ?string $helpText = null,
        public array $options = [],
        public mixed $defaultValue = null,
        public array $validationRules = [],
    ) {}
}
