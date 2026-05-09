<?php

declare(strict_types=1);

namespace Capell\PublicActions\Actions;

use Capell\PublicActions\Enums\PublicActionStatus;
use Capell\PublicActions\Models\PublicAction;
use Lorisleiva\Actions\Concerns\AsAction;

final class ListPublicActionOptionsAction
{
    use AsAction;

    /**
     * @return array<string, string>
     */
    public function handle(): array
    {
        return PublicAction::query()
            ->where('status', PublicActionStatus::Active)
            ->orderBy('name')
            ->get(['key', 'name'])
            ->mapWithKeys(static fn (PublicAction $action): array => [$action->key => $action->name])
            ->all();
    }
}
