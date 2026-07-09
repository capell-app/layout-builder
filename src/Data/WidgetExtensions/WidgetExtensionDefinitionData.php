<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use Capell\Admin\Contracts\Widgets\FilamentWidget;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionBatchPayloadResolver;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionDependencyResolver;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionStateUpcaster;
use Capell\LayoutBuilder\Exceptions\InvalidWidgetExtensionDefinitionException;
use Spatie\LaravelData\Data;

final class WidgetExtensionDefinitionData extends Data
{
    /**
     * @param  class-string<FilamentWidget>  $filamentWidget
     * @param  class-string<Data>  $inputData
     * @param  class-string<Data>  $renderData
     * @param  array<string, string>  $components  Runtime name to component/view identifier.
     * @param  list<string>  $resourceGroups
     * @param  array<string, mixed>  $defaultPresentationSettings
     * @param  list<array<string, mixed>>  $defaultInteractions
     * @param  class-string<WidgetExtensionStateUpcaster>|null  $stateUpcaster
     * @param  class-string<WidgetExtensionBatchPayloadResolver>|null  $batchPayloadResolver
     * @param  class-string<WidgetExtensionDependencyResolver>|null  $dependencyResolver
     */
    public function __construct(
        public string $key,
        public string $packageName,
        public int $stateVersion,
        public string $filamentWidget,
        public string $inputData,
        public string $renderData,
        public string $fallbackView,
        public array $components,
        public array $resourceGroups = [],
        public array $defaultPresentationSettings = [],
        public array $defaultInteractions = [],
        public WidgetExtensionCapabilitiesData $capabilities = new WidgetExtensionCapabilitiesData,
        public ?string $stateUpcaster = null,
        public ?string $batchPayloadResolver = null,
        public ?string $dependencyResolver = null,
    ) {
        $this->validateDefinition();
    }

    public function themeView(): string
    {
        return 'capell::widgets.' . $this->key;
    }

    public function equals(self $definition): bool
    {
        return $this->toArray() === $definition->toArray();
    }

    private function validateDefinition(): void
    {
        $vendor = explode('/', $this->packageName, 2)[0] ?? '';

        $this->assert(
            preg_match('/^[a-z0-9][a-z0-9-]*(\.[a-z0-9][a-z0-9-]*)+$/', $this->key) === 1
                && $vendor !== ''
                && str_starts_with($this->key, $vendor . '.'),
            'Widget extension definitions require a package-prefixed key such as [capell-app.slideshow].',
        );
        $this->assert(
            preg_match('/^[a-z0-9][a-z0-9_.-]*\/[a-z0-9][a-z0-9_.-]*$/', $this->packageName) === 1,
            'Widget extension definitions require a valid Composer package name.',
        );
        $this->assert($this->stateVersion > 0, 'Widget extension definitions require a positive state version.');
        $this->assertClassImplements($this->filamentWidget, FilamentWidget::class, 'Filament widget');
        $this->assert(
            $this->filamentWidget::getWidgetName() === $this->key,
            sprintf('Filament widget [%s] must expose the canonical widget key [%s].', $this->filamentWidget, $this->key),
        );
        $this->assertClassImplements($this->inputData, Data::class, 'input Data class');
        $this->assertClassImplements($this->renderData, Data::class, 'public render Data class');
        $this->assert(trim($this->fallbackView) !== '', 'Widget extension definitions require a package fallback Blade view.');
        $this->assert(
            isset($this->components['blade']) && trim($this->components['blade']) !== '',
            'Widget extension definitions require a Blade runtime component.',
        );
        $this->assertStringMap($this->components, 'runtime component');
        $this->assertStringList($this->resourceGroups, 'resource group');

        if ($this->stateUpcaster !== null) {
            $this->assertClassImplements($this->stateUpcaster, WidgetExtensionStateUpcaster::class, 'state upcaster');
        }

        if ($this->batchPayloadResolver !== null) {
            $this->assertClassImplements($this->batchPayloadResolver, WidgetExtensionBatchPayloadResolver::class, 'batch payload resolver');
        }

        if ($this->dependencyResolver !== null) {
            $this->assertClassImplements($this->dependencyResolver, WidgetExtensionDependencyResolver::class, 'dependency resolver');
        }
    }

    /** @param array<mixed> $values */
    private function assertStringList(array $values, string $label): void
    {
        foreach ($values as $key => $value) {
            $this->assert(is_int($key) && is_string($value) && trim($value) !== '', sprintf(
                'Widget extension %s values must be a list of non-empty strings.',
                $label,
            ));
        }
    }

    /** @param array<mixed> $values */
    private function assertStringMap(array $values, string $label): void
    {
        foreach ($values as $key => $value) {
            $this->assert(is_string($key) && $key !== '' && is_string($value) && trim($value) !== '', sprintf(
                'Widget extension %s values must map non-empty strings to non-empty strings.',
                $label,
            ));
        }
    }

    /** @param class-string $contract */
    private function assertClassImplements(string $class, string $contract, string $label): void
    {
        $this->assert(class_exists($class) && is_a($class, $contract, true), sprintf(
            'Widget extension %s [%s] must implement or extend [%s].',
            $label,
            $class,
            $contract,
        ));
    }

    private function assert(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new InvalidWidgetExtensionDefinitionException($message);
        }
    }
}
