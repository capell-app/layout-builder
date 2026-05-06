<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Livewire;

use Capell\Frontend\Contracts\AdminAccessCheckerInterface;
use Capell\FrontendAuthoring\Actions\UpdateEditableRegionAction;
use Capell\FrontendAuthoring\Data\EditableRegionPayloadData;
use Capell\FrontendAuthoring\Support\EditableRegionSigner;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Concerns\InteractsWithFormBuilder;
use Filament\FormBuilder\Contracts\HasFormBuilder;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component as LivewireComponent;

/**
 * @property Schema $form
 */
class EditRegionField extends LivewireComponent implements HasFormBuilder
{
    use InteractsWithFormBuilder;

    #[Locked]
    public string $payload;

    #[Locked]
    public string $label = '';

    #[Locked]
    public string $type = 'text';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(string $payload): void
    {
        $this->authorizeAdmin();

        $this->payload = $payload;
        $region = $this->region();
        $this->label = $region->label;
        $this->type = $region->type;

        $this->form->fill([
            'value' => $this->currentValue($region),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                $this->field(),
            ]);
    }

    public function save(): void
    {
        $this->authorizeAdmin();

        /** @var array{value?: mixed} $state */
        $state = $this->form->getState();
        $result = UpdateEditableRegionAction::run($this->region(), (string) ($state['value'] ?? ''));

        $this->dispatch(
            'capell-authoring-saved',
            cleared: $result['cleared'],
            urls: $result['urls'],
        );
    }

    public function render(): View
    {
        return view('capell::livewire.edit-region-field');
    }

    private function field(): Component
    {
        if ($this->type === 'text') {
            return TextInput::make('value')
                ->label($this->label)
                ->required()
                ->maxLength(65535);
        }

        return Textarea::make('value')
            ->label($this->label)
            ->required()
            ->rows($this->type === 'html' ? 14 : 7)
            ->maxLength(65535);
    }

    private function currentValue(EditableRegionPayloadData $region): string
    {
        $modelClass = $region->model;

        abort_unless(is_subclass_of($modelClass, Model::class), 403);

        /** @var Model $record */
        $record = $modelClass::query()->findOrFail($region->recordKey);

        if ($region->field === 'title' || $region->field === 'content') {
            return (string) $record->getAttribute($region->field);
        }

        if (str_starts_with($region->field, 'meta.')) {
            return (string) data_get((array) $record->getAttribute('meta'), substr($region->field, 5), '');
        }

        abort(403);
    }

    private function region(): EditableRegionPayloadData
    {
        return resolve(EditableRegionSigner::class)->decode($this->payload);
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();

        abort_unless($user instanceof AuthenticatableContract, 403);
        abort_unless(resolve(AdminAccessCheckerInterface::class)->isAdmin($user), 403);
    }
}
