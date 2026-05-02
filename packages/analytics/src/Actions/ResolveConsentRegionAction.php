<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Support\Consent\ConsentRegionResolver;
use Lorisleiva\Actions\Concerns\AsAction;

final class ResolveConsentRegionAction
{
    use AsAction;

    public function handle(): AnalyticsConsentRegion
    {
        return resolve(ConsentRegionResolver::class)->resolve();
    }
}
