<?php

declare(strict_types=1);

namespace Capell\Plugins\Manifest;

use Capell\Plugins\Data\PluginManifestData;
use Capell\Plugins\Enums\Capability;
use Capell\Plugins\Manifest\Exceptions\ManifestValidationException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

final readonly class ManifestValidator
{
    private Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator(max_errors: 10, stop_at_first_error: false);
    }

    public function validate(string $json): ManifestValidationResult
    {
        $decoded = json_decode($json, false);

        if ($decoded === null) {
            return new ManifestValidationResult(false, ['Invalid JSON: ' . json_last_error_msg()]);
        }

        $schemaJson = (string) file_get_contents($this->schemaPath());
        $schema = json_decode($schemaJson, false);

        $result = $this->validator->validate($decoded, $schema);

        $errors = [];

        if (! $result->isValid() && $result->error() instanceof ValidationError) {
            $formatter = new ErrorFormatter;
            $errors = $formatter->formatFlat(
                $result->error(),
                static function (ValidationError $error) use ($formatter): string {
                    $path = $formatter->formatErrorKey($error);
                    $message = $formatter->formatErrorMessage($error);

                    return $path !== '' ? sprintf('%s: %s', $path, $message) : $message;
                },
            );
        }

        // Custom capability validation (schema only checks items are strings).
        $capabilityErrors = $this->validateCapabilities($decoded);
        $errors = array_merge($errors, $capabilityErrors);

        return new ManifestValidationResult($errors === [], $errors);
    }

    public function validateOrFail(string $json): ManifestValidationResult
    {
        $result = $this->validate($json);

        if (! $result->isValid) {
            throw ManifestValidationException::fromErrors($result->errors);
        }

        return $result;
    }

    public function hydrate(string $json): PluginManifestData
    {
        $this->validateOrFail($json);

        /** @var array<string, mixed> $data */
        $data = json_decode($json, true);

        return PluginManifestData::from($data);
    }

    private function schemaPath(): string
    {
        return dirname(__DIR__, 2) . '/schema/plugin.v1.json';
    }

    /**
     * @return list<string>
     */
    private function validateCapabilities(mixed $decoded): array
    {
        if (! is_object($decoded) || ! isset($decoded->capabilities) || ! is_array($decoded->capabilities)) {
            return [];
        }

        $knownValues = array_map(
            static fn (Capability $capability): string => $capability->value,
            Capability::cases(),
        );

        $errors = [];

        foreach ($decoded->capabilities as $raw) {
            if (! is_string($raw)) {
                continue;
            }

            $base = explode(':', $raw, 2)[0];

            if (! in_array($base, $knownValues, true)) {
                $errors[] = 'Unknown capability: ' . $raw;
            }
        }

        return $errors;
    }
}
