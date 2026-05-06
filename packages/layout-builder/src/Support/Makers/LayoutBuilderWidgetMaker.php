<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Makers;

use Capell\Core\Data\Makers\MakerDefinitionData;
use Capell\Core\Data\Makers\MakerFileData;
use Capell\Core\Data\Makers\MakerInputData;
use Capell\Core\Data\Makers\MakerPreviewData;
use Capell\Core\Data\Makers\MakerResultData;
use Capell\Core\Support\Makers\AbstractFileMaker;
use Capell\LayoutBuilder\Actions\MakeWidgetAction;
use Illuminate\Support\Str;

class LayoutBuilderWidgetMaker extends AbstractFileMaker
{
    public function definition(): MakerDefinitionData
    {
        return new MakerDefinitionData('layout-builder.widget', 'LayoutBuilder Widget', 'Create LayoutBuilder widget files and registration snippets', 'LayoutBuilder', 'heroicon-o-squares-2x2', true, true);
    }

    public function run(MakerInputData $input): MakerResultData
    {
        $preview = $this->preview($input);
        $result = MakeWidgetAction::run(
            (string) ($input->values['name'] ?? ''),
            null,
            (bool) ($input->values['livewire'] ?? false),
            $input->force,
        );

        return new MakerResultData(
            maker: $input->maker,
            files: $preview->files->map(fn (MakerFileData $file): MakerFileData => new MakerFileData($file->path, $file->operation, file_exists($file->path), is_writable($file->path), $file->contents)),
            databaseRecords: collect(),
            commands: $preview->commands,
            notes: collect([$result->seederSnippet]),
            successful: true,
        );
    }

    protected function buildPreview(MakerInputData $input): MakerPreviewData
    {
        $studly = $this->studlyName($input);
        $kebab = Str::kebab($studly);
        $files = collect([
            $this->fileData(resource_path('views/widgets/' . $kebab . '.blade.php'), $this->renderStub(__DIR__ . '/../../../stubs/widget.view.stub', ['class' => $studly, 'name' => $kebab]), $input->force),
        ]);

        if (($input->values['livewire'] ?? false) === true) {
            $files->push($this->fileData(app_path('Livewire/Widgets/' . $studly . 'Widget.php'), $this->renderStub(__DIR__ . '/../../../stubs/widget.livewire.stub', ['class' => $studly . 'Widget', 'view' => 'widgets.livewire.' . $kebab]), $input->force));
            $files->push($this->fileData(resource_path('views/widgets/livewire/' . $kebab . '.blade.php'), $this->renderStub(__DIR__ . '/../../../stubs/widget.livewire-view.stub', []), $input->force));
        }

        return $this->previewData(
            $input,
            $files,
            collect(['php artisan capell:layout-builder-make-widget ' . $studly]),
            collect([MakeWidgetAction::make()->seederSnippet($kebab, Str::headline($studly))]),
        );
    }
}
