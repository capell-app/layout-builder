<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Makers;

use Capell\Core\Data\Makers\MakerDefinitionData;
use Capell\Core\Data\Makers\MakerFileData;
use Capell\Core\Data\Makers\MakerInputData;
use Capell\Core\Data\Makers\MakerPreviewData;
use Capell\Core\Data\Makers\MakerResultData;
use Capell\Core\Support\Makers\AbstractFileMaker;
use Capell\LayoutBuilder\Actions\MakeBlockAction;
use Illuminate\Support\Str;
use Override;

class LayoutBuilderBlockMaker extends AbstractFileMaker
{
    public function definition(): MakerDefinitionData
    {
        return new MakerDefinitionData('layout-builder.block', 'LayoutBuilder Block', 'Create LayoutBuilder block files and registration snippets', 'LayoutBuilder', 'heroicon-o-squares-2x2', true, true);
    }

    #[Override]
    public function run(MakerInputData $input): MakerResultData
    {
        $preview = $this->preview($input);
        $result = MakeBlockAction::run(
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
            $this->fileData(resource_path('views/blocks/' . $kebab . '.blade.php'), $this->renderStub(__DIR__ . '/../../../stubs/block.view.stub', ['class' => $studly, 'name' => $kebab]), $input->force),
        ]);

        if (($input->values['livewire'] ?? false) === true) {
            $files->push($this->fileData(app_path('Livewire/Blocks/' . $studly . 'Block.php'), $this->renderStub(__DIR__ . '/../../../stubs/block.livewire.stub', ['class' => $studly . 'Block', 'view' => 'blocks.livewire.' . $kebab]), $input->force));
            $files->push($this->fileData(resource_path('views/blocks/livewire/' . $kebab . '.blade.php'), $this->renderStub(__DIR__ . '/../../../stubs/block.livewire-view.stub', []), $input->force));
        }

        return $this->previewData(
            $input,
            $files,
            collect(['php artisan capell:layout-builder-make-block ' . $studly]),
            collect([MakeBlockAction::make()->seederSnippet($kebab, Str::headline($studly))]),
        );
    }
}
