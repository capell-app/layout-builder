<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Makers;

use Capell\Core\Data\Makers\MakerDefinitionData;
use Capell\Core\Data\Makers\MakerFileData;
use Capell\Core\Data\Makers\MakerInputData;
use Capell\Core\Data\Makers\MakerPreviewData;
use Capell\Core\Data\Makers\MakerResultData;
use Capell\Core\Support\Makers\AbstractFileMaker;
use Capell\LayoutBuilder\Actions\MakeElementAction;
use Illuminate\Support\Str;

class LayoutBuilderElementMaker extends AbstractFileMaker
{
    public function definition(): MakerDefinitionData
    {
        return new MakerDefinitionData('layout-builder.element', 'LayoutBuilder Element', 'Create LayoutBuilder element files and registration snippets', 'LayoutBuilder', 'heroicon-o-squares-2x2', true, true);
    }

    public function run(MakerInputData $input): MakerResultData
    {
        $preview = $this->preview($input);
        $result = MakeElementAction::run(
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
            $this->fileData(resource_path('views/elements/' . $kebab . '.blade.php'), $this->renderStub(__DIR__ . '/../../../stubs/element.view.stub', ['class' => $studly, 'name' => $kebab]), $input->force),
        ]);

        if (($input->values['livewire'] ?? false) === true) {
            $files->push($this->fileData(app_path('Livewire/Elements/' . $studly . 'Element.php'), $this->renderStub(__DIR__ . '/../../../stubs/element.livewire.stub', ['class' => $studly . 'Element', 'view' => 'elements.livewire.' . $kebab]), $input->force));
            $files->push($this->fileData(resource_path('views/elements/livewire/' . $kebab . '.blade.php'), $this->renderStub(__DIR__ . '/../../../stubs/element.livewire-view.stub', []), $input->force));
        }

        return $this->previewData(
            $input,
            $files,
            collect(['php artisan capell:layout-builder-make-element ' . $studly]),
            collect([MakeElementAction::make()->seederSnippet($kebab, Str::headline($studly))]),
        );
    }
}
