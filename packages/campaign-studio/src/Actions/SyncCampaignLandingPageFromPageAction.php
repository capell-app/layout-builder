<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Models\CampaignGroup;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Capell\Core\Contracts\Pageable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;

final class SyncCampaignLandingPageFromPageAction
{
    use AsAction;

    public function handle(Pageable $page): ?CampaignLandingPage
    {
        if (! $page instanceof Model) {
            return null;
        }

        if (! Schema::hasTable('campaign_landing_pages')) {
            return null;
        }

        $campaignMeta = $this->campaignMeta($page);
        $campaignGroupId = $campaignMeta['campaign_group_id'] ?? null;
        $isLandingPage = (bool) ($campaignMeta['is_landing_page'] ?? false);

        if (! $isLandingPage || ! is_numeric($campaignGroupId)) {
            CampaignLandingPage::query()
                ->where('page_id', $page->getKey())
                ->delete();

            return null;
        }

        if (! Schema::hasTable('campaign_groups')) {
            return null;
        }

        $campaignGroup = CampaignGroup::query()->find((int) $campaignGroupId);

        if (! $campaignGroup instanceof CampaignGroup) {
            return null;
        }

        $data = [
            'campaign_group_id' => $campaignGroup->getKey(),
            'page_id' => $page->getKey(),
            'headline' => $page->getAttribute('name'),
            'primary_goal_id' => $this->integerValue($campaignMeta['primary_goal_id'] ?? null),
            'utm_content' => $this->stringValue($campaignMeta['utm_content'] ?? null),
            'utm_term' => $this->stringValue($campaignMeta['utm_term'] ?? null),
            'is_primary' => false,
        ];

        return CampaignLandingPage::query()->updateOrCreate(
            ['page_id' => $page->getKey()],
            $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function campaignMeta(Model $page): array
    {
        $meta = $page->getAttribute('meta');

        if (! is_array($meta)) {
            return [];
        }

        $campaignMeta = $meta['campaign'] ?? null;

        return is_array($campaignMeta) ? $campaignMeta : [];
    }

    private function integerValue(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
