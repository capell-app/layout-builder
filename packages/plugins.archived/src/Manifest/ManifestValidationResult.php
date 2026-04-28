<?php

declare(strict_types=1);

namespace Capell\Plugins\Manifest;

final readonly class ManifestValidationResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public bool $isValid,
        public array $errors = [],
    ) {}
}
