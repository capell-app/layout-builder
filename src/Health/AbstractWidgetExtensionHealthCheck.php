<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Health;

use Capell\Admin\Contracts\Widgets\FilamentWidget;
use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Collection;
use Throwable;

abstract class AbstractWidgetExtensionHealthCheck implements ChecksExtensionHealth
{
    abstract protected static function definitionKey(): string;

    /** @return class-string<FilamentWidget> */
    abstract protected static function filamentWidget(): string;

    abstract protected static function fallbackView(): string;

    /** @return list<string> */
    abstract protected static function resourceGroups(): array;

    /** @return Collection<int, DoctorCheckResultData> */
    public static function runDiagnostics(): Collection
    {
        $definition = resolve(WidgetExtensionRegistry::class)->definition(static::definitionKey());
        $filamentWidget = static::filamentWidget();
        $resourceRegistry = resolve(FrontendResourceRegistry::class);

        try {
            $blockResolves = $filamentWidget::make()->getName() === static::definitionKey();
        } catch (Throwable) {
            $blockResolves = false;
        }

        $resourceGroupsResolve = collect(static::resourceGroups())
            ->every(static fn (string $group): bool => $resourceRegistry->has($group));

        return collect([
            self::result('Widget definition registration', $definition !== null),
            self::result('Filament block resolution', $blockResolves),
            self::result('Package fallback view', resolve(ViewFactory::class)->exists(static::fallbackView())),
            self::result('Frontend resource groups', $resourceGroupsResolve),
        ]);
    }

    public static function passed(): bool
    {
        return static::runDiagnostics()
            ->every(static fn (DoctorCheckResultData $result): bool => $result->passed);
    }

    private static function result(string $label, bool $passed): DoctorCheckResultData
    {
        return new DoctorCheckResultData(
            label: $label,
            passed: $passed,
            message: $passed ? $label . ' is healthy.' : $label . ' is unavailable.',
            remediation: $passed ? null : 'Enable the widget package and run capell:doctor again.',
        );
    }
}
