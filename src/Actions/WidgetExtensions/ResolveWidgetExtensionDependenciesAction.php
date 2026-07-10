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
                foreach ($identifiers as $identifier) {
                    $dependency = is_string($identifier) ? $this->parse($identifier) : null;
                    if ($dependency !== null) {
                        $dependencies[$dependency->modelType . ':' . $dependency->modelId] = $dependency;
                    }
                }
            } catch (Throwable $throwable) {
                $this->diagnostic($discovered->definition->key, $throwable);
            }
        }

        return array_values($dependencies);
    }

    private function parse(string $identifier): ?WidgetExtensionDependencyData
    {
        if (preg_match('/^media:([1-9][0-9]*)$/', $identifier, $matches) === 1) {
            return new WidgetExtensionDependencyData(Media::class, (int) $matches[1], 'uses_media');
        }

        if (preg_match('/^content:(page|layout|widget):([1-9][0-9]*)$/', $identifier, $matches) === 1) {
            return new WidgetExtensionDependencyData(self::CONTENT_TYPES[$matches[1]], (int) $matches[2], 'uses_content');
        }

        return null;
    }

    private function diagnostic(string $widgetKey, Throwable $throwable): void
    {
        try {
            Log::warning('Widget extension dependency resolution failed.', [
                'widget_key' => $widgetKey,
                'failure_type' => $throwable::class,
            ]);
        } catch (Throwable) {
            // Dependency extraction is best effort and diagnostics are isolated.
        }
    }
}
