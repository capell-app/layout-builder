<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Models\CampaignGroup;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class ResolveCampaignFromUrlAction
{
    use AsAction;

    public function handle(string $url): ?CampaignGroup
    {
        $utmCampaign = $this->utmCampaign($url);

        if ($utmCampaign !== null) {
            $campaignGroup = CampaignGroup::query()
                ->active()
                ->where(function (Builder $builder) use ($utmCampaign): void {
                    $builder
                        ->where('slug', $utmCampaign)
                        ->orWhere('utm_campaign', $utmCampaign);
                })
                ->first();

            if ($campaignGroup instanceof CampaignGroup) {
                return $campaignGroup;
            }
        }

        $path = $this->path($url);

        if ($path === null) {
            return null;
        }

        $landingPage = CampaignLandingPage::query()
            ->whereHas('page.pageUrls', function (Builder $builder) use ($path): void {
                $builder->where('url', $path);
            })
            ->with('campaignGroup')
            ->first();

        return $landingPage?->campaignGroup;
    }

    private function utmCampaign(string $url): ?string
    {
        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return null;
        }

        $parameters = [];
        parse_str($query, $parameters);
        $utmCampaign = $parameters['utm_campaign'] ?? null;

        return is_string($utmCampaign) && trim($utmCampaign) !== '' ? $utmCampaign : null;
    }

    private function path(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return '/';
        }

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }
}
