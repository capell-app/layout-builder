<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\TranslatableContentDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Component as LivewireComponent;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Component&CanEntangleWithSingularRelationships $component, LivewireComponent&HasSchemas $livewire)
 */
class SaveFormComponentRelationshipAction
{
    use AsFake;
    use AsObject;

    public function handle(Component&CanEntangleWithSingularRelationships $component, LivewireComponent&HasSchemas $livewire): void
    {
        $record = $component->getCachedExistingRecord();

        if (! $component->hasRelationship()) {
            $record?->delete();

            return;
        }

        $childSchema = $component->getChildSchema();

        if (! $childSchema instanceof Schema) {
            return;
        }

        $data = $childSchema->getState(shouldCallHooksBefore: false);

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

        if (! $relationship instanceof Relation || $relatedModel === null) {
            return;
        }

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
