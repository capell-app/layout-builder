<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetExtensions;

use Capell\LayoutBuilder\Data\WidgetExtensions\DiscoveredWidgetExtensionData;
use ReflectionClass;
use RuntimeException;
use Spatie\LaravelData\Data;

final readonly class WidgetExtensionInputFactory
{
    public function __construct(
        private WidgetExtensionContentStateProcessor $processor,
    ) {}

    public function make(DiscoveredWidgetExtensionData $discovered): Data
    {
        $processed = $this->processor->process($discovered->definition->key, $discovered->widget);
        $data = is_array($processed['data'] ?? null) ? $processed['data'] : [];
        $metadata = is_array($data['__capell'] ?? null) ? $data['__capell'] : [];

        if (($metadata['state_version'] ?? null) !== $discovered->definition->stateVersion) {
            throw new RuntimeException('Widget state could not be upgraded.');
        }

        $clean = $this->removeAuthoringMetadata($data);
        $inputClass = $discovered->definition->inputData;
        $this->assertNoUnexpectedFields($inputClass, $clean);
        $input = $inputClass::validateAndCreate($clean);

        if (! $input instanceof $inputClass) {
            throw new RuntimeException('Widget input has an invalid type.');
        }

        return $input;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function removeAuthoringMetadata(array $data): array
    {
        $reserved = ['__capell', 'editor_url', 'field_path', 'selector', 'signed_url', 'package_name'];
        $clean = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $reserved, true)) {
                continue;
            }

            $clean[$key] = $this->removeNestedAuthoringMetadata($value, $reserved);
        }

        return $clean;
    }

    /** @param list<string> $reserved */
    private function removeNestedAuthoringMetadata(mixed $value, array $reserved): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $clean = [];
        foreach ($value as $key => $child) {
            if (is_string($key) && in_array($key, $reserved, true)) {
                continue;
            }

            $clean[$key] = $this->removeNestedAuthoringMetadata($child, $reserved);
        }

        return $clean;
    }

    /** @param class-string<Data> $dataClass
     * @param  array<string, mixed>  $state
     */
    private function assertNoUnexpectedFields(string $dataClass, array $state): void
    {
        $constructor = (new ReflectionClass($dataClass))->getConstructor();
        $allowed = collect($constructor?->getParameters() ?? [])->map->getName()->all();

        if (array_diff(array_keys($state), $allowed) !== []) {
            throw new RuntimeException('Unexpected widget input fields.');
        }
    }
}
