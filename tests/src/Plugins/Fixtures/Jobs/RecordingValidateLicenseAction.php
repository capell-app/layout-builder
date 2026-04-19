<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins\Fixtures\Jobs;

use Capell\Plugins\Actions\ValidateLicenseAction;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Throwable;

/**
 * Test double that records the IDs of licenses passed to handle(). Optionally
 * throws for specific IDs so we can verify iteration continues after failures.
 */
class RecordingValidateLicenseAction extends ValidateLicenseAction
{
    /**
     * @var array<int, int>
     */
    private array $seenIds = [];

    /**
     * @param  array<int, Throwable>  $throwsById
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
     * @return array<int, int>
     */
    public function getSeenIds(): array
    {
        return $this->seenIds;
    }
}
