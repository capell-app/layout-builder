<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\BlockScaffoldData;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static BlockScaffoldData run(string $name, ?string $viewsDirectory = null, bool $livewire = false, bool $force = false)
 */
class MakeBlockAction
{
    use AsFake;
    use AsObject;

    public function handle(string $name, ?string $viewsDirectory = null, bool $livewire = false, bool $force = false): BlockScaffoldData
    {
        $studly = Str::studly($name);

        throw_if($studly === '', RuntimeException::class, 'Widget name is required.');

        $kebab = Str::kebab($studly);
        $headline = Str::headline($studly);

        $viewDirectory = $viewsDirectory ?? resource_path('views/blocks');
        $viewPath = $viewDirectory . DIRECTORY_SEPARATOR . $kebab . '.blade.php';

        $created = false;

        if (! is_dir($viewDirectory)) {
            mkdir($viewDirectory, 0755, true);
        }

        if ($force || ! file_exists($viewPath)) {
            $stubPath = __DIR__ . '/../../stubs/block.view.stub';
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

        return new BlockScaffoldData(
            viewPath: $viewPath,
            created: $created,
            seederSnippet: $this->seederSnippet($kebab, $headline),
        );
    }

    public function seederSnippet(string $kebab, string $headline): string
    {
        return <<<PHP
            use Capell\Core\Models\Blueprint;
            use Capell\LayoutBuilder\Models\Widget;

            \$type = Blueprint::firstOrCreate(
                ['type' => 'block', 'key' => '{$kebab}'],
                ['name' => '{$headline}', 'status' => true],
            );

            Widget::firstOrCreate(
                ['blueprint_id' => \$type->id, 'key' => '{$kebab}'],
                [
                    'name' => '{$headline}',
                    'status' => true,
                    'meta' => ['component' => 'blocks.{$kebab}'],
                ],
            );
            PHP;
    }

    private function writeLivewireFiles(string $studly, string $kebab, bool $force): void
    {
        $classDirectory = app_path('Livewire/Blocks');
        $viewDirectory = resource_path('views/blocks/livewire');

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
                [$studly . 'Widget', 'blocks.livewire.' . $kebab],
                (string) file_get_contents(__DIR__ . '/../../stubs/block.livewire.stub'),
            ));
        }

        if ($force || ! file_exists($viewPath)) {
            file_put_contents($viewPath, (string) file_get_contents(__DIR__ . '/../../stubs/block.livewire-view.stub'));
        }
    }
}
