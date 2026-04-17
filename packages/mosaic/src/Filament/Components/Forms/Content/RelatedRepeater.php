<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Content;

use Capell\Mosaic\Actions\ReplicateContentAction;
use Capell\Mosaic\Filament\Components\Forms\ContentSelect;
use Capell\Mosaic\Models\Content;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;

class RelatedRepeater
{
    public static function make(Schema $schema): Repeater
    {
        return Repeater::make('related')
            ->label(__('capell-mosaic::form.related_contents'))
            ->statePath('related')
            ->hiddenLabel()
            ->cloneable()
            ->cloneAction(
                fn (Action $action): Action => $action
                    ->visible(
                        fn (array $state, array $arguments): bool => $state[$arguments['item']]['content_id'] ?? false,
                    )
                    ->action(function (Repeater $component, array $arguments): void {
                        $newUuid = $component->generateUuid();

                        $items = $component->getState();

                        $newData = $items[$arguments['item']];

                        $existingContent = Content::query()->find($newData['content_id']);

                        throw_unless($existingContent, Exception::class, 'Content not found with ID: ' . $newData['content_id']);

                        $newContent = ReplicateContentAction::run($existingContent);

                        $newData['content_id'] = $newContent->id;

                        if (! in_array($newUuid, [null, '', '0'], true)) {
                            $items[$newUuid] = $newData;
                        } else {
                            $items[] = $newData;
                        }

                        $component->state($items);

                        $component->collapsed(false, shouldMakeComponentCollapsible: false);

                        $component->callAfterStateUpdated();
                    }),
            )
            ->simple(
                ContentSelect::make('content_id')
                    ->hiddenLabel()
                    ->required()
                    ->preload(fn (string $operation): bool => in_array($operation, ['create', 'createOption'], true))
                    ->when(
                        $schema->isCreating(),
                        fn (ContentSelect $component): ContentSelect => $component->withCreateForm(),
                        fn (ContentSelect $component): ContentSelect => $component->withEditForm(),
                    ),
            );
    }
}
