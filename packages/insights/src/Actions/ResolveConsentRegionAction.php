<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Support\Consent\ConsentRegionResolver;
use Lorisleiva\Actions\Concerns\AsAction;

final class ResolveConsentRegionAction
{
    use AsAction;

    public function handle(): InsightsConsentRegion
    {
        return resolve(ConsentRegionResolver::class)->resolve();
    }
}
