<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * @param  class-string<Model>|null  $modelClass
 */
final class ReleaseWorkspaceItemData extends Data
{
    public function __construct(
        public readonly string $source,
        public readonly string $label,
        public readonly ?string $modelClass,
        public readonly null|int|string $modelId,
        public readonly string $changeType,
        public readonly string $status,
        public readonly ?string $url,
    ) {}
}
