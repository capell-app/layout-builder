<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Fixtures\Forms;

use Capell\Mosaic\Filament\Components\Forms\Page\HeroEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class HeroEditorTestFixture extends Component implements HasForms
{
    use InteractsWithForms;

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
