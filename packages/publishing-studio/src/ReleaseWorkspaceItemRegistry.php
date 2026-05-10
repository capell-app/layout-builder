<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Contracts\ReleaseWorkspaceItemContributor;

final class ReleaseWorkspaceItemRegistry
{
    /** @var list<class-string<ReleaseWorkspaceItemContributor>> */
    private array $contributors = [];

    /**
     * @param  class-string<ReleaseWorkspaceItemContributor>  $contributor
     */
    public function register(string $contributor): void
    {
        if (! in_array($contributor, $this->contributors, true)) {
            $this->contributors[] = $contributor;
        }
    }

    /**
     * @return list<class-string<ReleaseWorkspaceItemContributor>>
     */
    public function contributors(): array
    {
        return $this->contributors;
    }
}
