<?php

declare(strict_types=1);

namespace Capell\Plugins\Tests\Fixtures\Jobs;

use Capell\Plugins\Actions\ValidateLicenseAction;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Throwable;

final class RecordingValidateLicenseAction extends ValidateLicenseAction
{
    /**
     * @var array<int, int|string>
     */
    private array $seenIds = [];

    /**
     * @param  array<int|string, Throwable>  $throwsById
     */
    public function __construct(
        AnystackClient $anystackClient,
        private readonly array $throwsById = [],
    ) {
        parent::__construct($anystackClient);
    }

    public function handle(MarketplacePluginLicense $license): MarketplacePluginLicense
    {
        $this->seenIds[] = $license->id;

        if (isset($this->throwsById[$license->id])) {
            throw $this->throwsById[$license->id];
        }

        return $license;
    }

    /**
     * @return array<int, int|string>
     */
    public function getSeenIds(): array
    {
        return $this->seenIds;
    }
}
