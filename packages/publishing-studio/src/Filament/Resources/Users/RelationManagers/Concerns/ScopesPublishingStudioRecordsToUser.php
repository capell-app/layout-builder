<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ScopesPublishingStudioRecordsToUser
{
    /**
     * @return array{type: string, id: int|string|null}
     */
    protected static function userMorphKey(Model $user): array
    {
        return [
            'type' => $user->getMorphClass(),
            'id' => $user->getKey(),
        ];
    }
}
