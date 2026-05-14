<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\WidgetScaffoldData;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static WidgetScaffoldData run(string $name, ?string $viewsDirectory = null, bool $livewire = false, bool $force = false)
 */
class MakeWidgetAction
{
    use AsFake;
    use AsObject;

    public function handle(string $name, ?string $viewsDirectory = null, bool $livewire = false, bool $force = false): WidgetScaffoldData
    {
        $studly = Str::studly($name);

        throw_if($studly === '', RuntimeException::class, 'Widget name is required.');

        $kebab = Str::kebab($studly);
        $headline = Str::headline($studly);

        $viewDirectory = $viewsDirectory ?? resource_path('views/widgets');
        $viewPath = $viewDirectory . DIRECTORY_SEPARATOR . $kebab . '.blade.php';

        $created = false;

        if (! is_dir($viewDirectory)) {
            mkdir($viewDirectory, 0755, true);
        }

        if ($force || ! file_exists($viewPath)) {
            $stubPath = __DIR__ . '/../../stubs/widget.view.stub';
            $stub = (string) file_get_contents($stubPath);

            $contents = str_replace(
                ['{{ class }}', '{{ name }}'],
                [$studly, $kebab],
                $stub,
            );

            file_put_contents($viewPath, $contents);

            $created = true;
        }

        if ($livewire) {
            $this->writeLivewireFiles($studly, $kebab, $force);
        }

        return new WidgetScaffoldData(
            viewPath: $viewPath,
            created: $created,
            seederSnippet: $this->seederSnippet($kebab, $headline),
        );
    }

    public function seederSnippet(string $kebab, string $headline): string
    {
        return <<<PHP
            use Capell\Core\Models\Type;
            use Capell\Core\Models\Widget;

            \$type = Type::firstOrCreate(
                ['type' => 'widget', 'key' => '{$kebab}'],
                ['name' => '{$headline}', 'status' => true],
            );

            Widget::firstOrCreate(
                ['blueprint_id' => \$type->id, 'key' => '{$kebab}'],
                [
                    'name' => '{$headline}',
                    'status' => true,
                    'meta' => ['component' => 'widgets.{$kebab}'],
                ],
            );
            PHP;
    }

    private function writeLivewireFiles(string $studly, string $kebab, bool $force): void
    {
        $classDirectory = app_path('Livewire/Widgets');
        $viewDirectory = resource_path('views/widgets/livewire');

        if (! is_dir($classDirectory)) {
            mkdir($classDirectory, 0755, true);
        }

        if (! is_dir($viewDirectory)) {
            mkdir($viewDirectory, 0755, true);
        }

        $classPath = $classDirectory . DIRECTORY_SEPARATOR . $studly . 'Widget.php';
        $viewPath = $viewDirectory . DIRECTORY_SEPARATOR . $kebab . '.blade.php';

        if ($force || ! file_exists($classPath)) {
            file_put_contents($classPath, str_replace(
                ['{{ class }}', '{{ view }}'],
                [$studly . 'Widget', 'widgets.livewire.' . $kebab],
                (string) file_get_contents(__DIR__ . '/../../stubs/widget.livewire.stub'),
            ));
        }

        if ($force || ! file_exists($viewPath)) {
            file_put_contents($viewPath, (string) file_get_contents(__DIR__ . '/../../stubs/widget.livewire-view.stub'));
        }
    }
}
