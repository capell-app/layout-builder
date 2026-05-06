<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Exceptions;

use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonImmutable;
use RuntimeException;

class ReleaseWindowClosedException extends RuntimeException
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly ?CarbonImmutable $nextOpensAt = null,
    ) {
        $detail = $nextOpensAt instanceof CarbonImmutable
            ? ' Next window opens at ' . $nextOpensAt->toDateTimeString() . '.'
            : '';

        parent::__construct(sprintf(
            'Workspace #%d cannot be published: the release window is currently closed.%s',
            $workspace->id,
            $detail,
        ));
    }
}
