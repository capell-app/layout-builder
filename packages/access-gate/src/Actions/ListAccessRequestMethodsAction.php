<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Contracts\AccessRequestMethod;
use Capell\AccessGate\Data\AccessRequestMethodData;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Support\AccessRequestMethodRegistry;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

final class ListAccessRequestMethodsAction
{
    use AsAction;

    public function __construct(
        private readonly AccessRequestMethodRegistry $methods,
    ) {}

    /**
     * @return Collection<int, AccessRequestMethodData>
     */
    public function handle(Area $area, ?string $requestedUrl = null): Collection
    {
        return collect($this->methods->all())
            ->filter(fn (AccessRequestMethod $method): bool => $method->isEnabled($area))
            ->map(fn (AccessRequestMethod $method): AccessRequestMethodData => new AccessRequestMethodData(
                key: $method->key(),
                label: $method->label(),
                url: $method->url($area, $requestedUrl),
                primary: $method->isPrimary($area),
                description: $method->description(),
            ))
            ->sortByDesc(fn (AccessRequestMethodData $method): bool => $method->primary)
            ->values();
    }
}
