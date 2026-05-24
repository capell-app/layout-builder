<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Livewire\Component as LivewireComponent;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array<array-key, mixed> run(Component|CanEntangleWithSingularRelationships $component, LivewireComponent $livewire): void
 */
class SaveFormComponentRelationshipAction
{
    use AsObject;

    public function handle(Component|CanEntangleWithSingularRelationships $component, LivewireComponent&HasSchemas $livewire): void
    {
        $record = $component->getCachedExistingRecord();

        if (! $component->hasRelationship()) {
            $record?->delete();

            return;
        }

        $data = $component->getChildSchema()->getState(shouldCallHooksBefore: false);

        $translatableContentDriver = $livewire->makeFilamentTranslatableContentDriver();

        if ($record instanceof Model) {
            $data = $component->mutateRelationshipDataBeforeSave($data);

            $translatableContentDriver instanceof TranslatableContentDriver ?
                $translatableContentDriver->updateRecord($record, $data) :
                $record->fill($data)->save();

            $component->cachedExistingRecord($record);

            return;
        }

        $relationship = $component->getRelationship();
        $relatedModel = $component->getRelatedModel();

        $data = $component->mutateRelationshipDataBeforeCreate($data);

        if ($translatableContentDriver instanceof TranslatableContentDriver) {
            $record = $translatableContentDriver->makeRecord($relatedModel, $data);
        } else {
            $record = new $relatedModel;
            $record->fill($data);
        }

        if ($relationship instanceof BelongsTo) {
            $record->save();
            $relationship->associate($record);
            $relationship->getParent()->save();
        } else {
            $relationship->save($record);
        }

        $component->cachedExistingRecord($record);
    }
}
