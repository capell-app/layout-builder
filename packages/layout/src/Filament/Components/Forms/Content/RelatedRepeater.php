<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Layout\Actions\ReplicateContentAction;
use Capell\Layout\Models\Content;
use Exception;
use Filament\Forms;

class RelatedRepeater
{
    public static function make(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('related')
            ->label(__('capell-admin::form.related_contents'))
            ->statePath('related')
            ->hiddenLabel()
            ->cloneable()
            ->cloneAction(
                fn (Forms\Components\Actions\Action $action): Forms\Components\Actions\Action => $action
                    ->visible(
                        fn (array $state, array $arguments): bool => $state[$arguments['item']]['content_id'] ?? false
                    )
                    ->action(function (Forms\Components\Repeater $component, array $arguments): void {
                        $newUuid = $component->generateUuid();

                        $items = $component->getState();

                        $newData = $items[$arguments['item']];

                        $existingContent = Content::query()->withDrafts()->find($newData['content_id']);

                        if (! $existingContent) {
                            throw new Exception('Content not found with ID: '.$newData['content_id']);
                        }

                        $newContent = ReplicateContentAction::run($existingContent);

                        $newData['content_id'] = $newContent->id;

                        if ($newUuid) {
                            $items[$newUuid] = $newData;
                        } else {
                            $items[] = $newData;
                        }

                        $component->state($items);

                        $component->collapsed(false, shouldMakeComponentCollapsible: false);

                        $component->callAfterStateUpdated();
                    })
            )
            ->simple(
                ContentSelect::make('content_id')
                    ->hiddenLabel()
                    ->required()
                    ->preload(fn (string $operation): bool => in_array($operation, ['create', 'createOption'], true))
                    ->withEditForm()
                    ->withCreateForm(),
            );
    }
}
