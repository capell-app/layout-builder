<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Models\CampaignLandingPage;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class ResolveCampaignLandingPageFromUrlAction
{
    use AsAction;

    public function handle(?string $url): ?CampaignLandingPage
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $path = $this->path($url);

        return CampaignLandingPage::query()
            ->whereHas('page.pageUrls', function (Builder $builder) use ($path): void {
                $builder->where('url', $path);
            })
            ->with(['campaignGroup', 'primaryGoal'])
            ->first();
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
