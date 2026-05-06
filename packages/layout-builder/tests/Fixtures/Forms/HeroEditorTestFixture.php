<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\Forms;

use Capell\LayoutBuilder\Filament\Components\Forms\Page\HeroEditor;
use Filament\FormBuilder\Concerns\InteractsWithFormBuilder;
use Filament\FormBuilder\Contracts\HasFormBuilder;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class HeroEditorTestFixture extends Component implements HasFormBuilder
{
    use InteractsWithFormBuilder;

    public ?Model $record = null;

    public function form(Schema $configurator): Schema
    {
        return $configurator
            ->model($this->record)
            ->components([HeroEditor::make()]);
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
