<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetExtensions;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionDependencyResolver;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDependencyData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionInputFactory;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionStateWalker;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ResolveWidgetExtensionDependenciesAction
{
    private const int MAX_DEPENDENCIES_PER_WIDGET = 64;

    private const int MAX_DEPENDENCIES_TOTAL = 512;

    /** @var array<string, class-string<Model>> */
    private const array CONTENT_TYPES = [
        'page' => Page::class,
        'layout' => Layout::class,
        'widget' => Widget::class,
    ];

    public function __construct(
        private WidgetExtensionStateWalker $walker,
        private WidgetExtensionInputFactory $inputFactory,
        private Container $container,
    ) {}

    /** @param array<mixed> $sources
     * @return list<WidgetExtensionDependencyData>
     */
    public function resolve(array $sources): array
    {
        $dependencies = [];
        $acceptedCount = 0;

        foreach ($this->walker->walk($sources) as $discovered) {
            $resolverClass = $discovered->definition->dependencyResolver;
            if ($resolverClass === null) {
                continue;
            }

            try {
                $resolver = $this->container->make($resolverClass);
                if (! $resolver instanceof WidgetExtensionDependencyResolver) {
                    continue;
                }

                $identifiers = $resolver->resolve($this->inputFactory->make($discovered));
                foreach ($identifiers as $index => $identifier) {
                    if ($index >= self::MAX_DEPENDENCIES_PER_WIDGET) {
                        $this->diagnostic($discovered->definition->key, 'Widget dependency output exceeded the per-widget limit.');
                        break;
                    }

                    $dependency = is_string($identifier) ? $this->parse($identifier) : null;
                    if ($dependency !== null) {
                        $dependencies[$dependency->modelType . ':' . $dependency->modelId] = $dependency;
                        $acceptedCount++;

                        if ($acceptedCount >= self::MAX_DEPENDENCIES_TOTAL) {
                            $this->diagnostic($discovered->definition->key, 'Widget dependency output exceeded the total limit.');

                            return array_values($dependencies);
                        }
                    } else {
                        $this->diagnostic($discovered->definition->key, 'Widget dependency identifier was rejected.');
                    }
                }
            } catch (Throwable $throwable) {
                $this->diagnostic($discovered->definition->key, 'Widget extension dependency resolution failed.', $throwable);
            }
        }

        return array_values($dependencies);
    }

    private function parse(string $identifier): ?WidgetExtensionDependencyData
    {
        if (preg_match('/^media:([1-9][0-9]*)$/', $identifier, $matches) === 1) {
            $modelId = $this->positiveId($matches[1]);

            return $modelId === null ? null : new WidgetExtensionDependencyData(Media::class, $modelId, 'uses_media');
        }

        if (preg_match('/^content:(page|layout|widget):([1-9][0-9]*)$/', $identifier, $matches) === 1) {
            $modelId = $this->positiveId($matches[2]);

            return $modelId === null ? null : new WidgetExtensionDependencyData(self::CONTENT_TYPES[$matches[1]], $modelId, 'uses_content');
        }

        return null;
    }

    private function positiveId(string $digits): ?int
    {
        $maximum = (string) PHP_INT_MAX;
        if (strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            return null;
        }

        $identifier = filter_var($digits, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => PHP_INT_MAX],
        ]);

        return is_int($identifier) ? $identifier : null;
    }

    private function diagnostic(string $widgetKey, string $message, ?Throwable $throwable = null): void
    {
        try {
            Log::warning($message, array_filter([
                'widget_key' => $widgetKey,
                'failure_type' => $throwable === null ? null : $throwable::class,
            ]));
        } catch (Throwable) {
            // Dependency extraction is best effort and diagnostics are isolated.
        }
    }
}
