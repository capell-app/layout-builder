<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Capell\Mosaic\Data\WidgetScaffoldData;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static WidgetScaffoldData run(string $name, ?string $viewsDirectory = null)
 */
class MakeWidgetAction
{
    use AsFake;
    use AsObject;

    public function handle(string $name, ?string $viewsDirectory = null): WidgetScaffoldData
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

        if (! file_exists($viewPath)) {
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

        return new WidgetScaffoldData(
            viewPath: $viewPath,
            created: $created,
            seederSnippet: $this->buildSeederSnippet($kebab, $headline),
        );
    }

    private function buildSeederSnippet(string $kebab, string $headline): string
    {
        return <<<PHP
            use Capell\Core\Models\Type;
            use Capell\Mosaic\Models\Widget;

            \$type = Type::firstOrCreate(
                ['type' => 'widget', 'key' => '{$kebab}'],
                ['name' => '{$headline}', 'status' => true],
            );

            Widget::firstOrCreate(
                ['type_id' => \$type->id, 'key' => '{$kebab}'],
                [
                    'name' => '{$headline}',
                    'status' => true,
                    'meta' => ['component' => 'widgets.{$kebab}'],
                ],
            );
            PHP;
    }
}
