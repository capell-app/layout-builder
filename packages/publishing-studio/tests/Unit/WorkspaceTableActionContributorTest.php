<?php

declare(strict_types=1);

use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Tables\PublishingStudioTable;
use Filament\Actions\Action;

it('defines the workspace table action contributor tag', function (): void {
    expect(WorkspaceTableActionContributor::TAG)
        ->toBe('capell.publishing-studio.table_action_contributors');
});

it('inserts tagged contributor actions after preview and before validate', function (): void {
    $contributorAbstract = WorkspaceTableActionContributor::class . '.test';

    app()->bind($contributorAbstract, fn (): WorkspaceTableActionContributor => new class implements WorkspaceTableActionContributor
    {
        public function actions(): array
        {
            return [
                Action::make('peekless-preview'),
            ];
        }
    });

    app()->tag([$contributorAbstract], WorkspaceTableActionContributor::TAG);

    $tableReflection = new ReflectionClass(PublishingStudioTable::class);
    $recordActionsMethod = $tableReflection->getMethod('getRecordActions');

    $actionNames = collect($recordActionsMethod->invoke(null))
        ->filter(fn (object $action): bool => method_exists($action, 'getName'))
        ->map(fn (object $action): string => $action->getName())
        ->values();

    expect($actionNames->all())
        ->toContain('preview')
        ->toContain('peekless-preview')
        ->toContain('validate')
        ->and($actionNames->search('peekless-preview'))->toBeGreaterThan($actionNames->search('preview'))
        ->and($actionNames->search('peekless-preview'))->toBeLessThan($actionNames->search('validate'));
});
