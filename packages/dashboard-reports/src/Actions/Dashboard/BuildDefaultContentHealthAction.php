<?php

declare(strict_types=1);

namespace Capell\DashboardReports\Actions\Dashboard;

use Capell\Admin\Data\Dashboard\ContentHealthData;
use Capell\Admin\Data\Dashboard\ContentHealthIssueData;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Page;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\LaravelData\DataCollection;

final class BuildDefaultContentHealthAction
{
    use AsObject;

    public function handle(int $staleDays = 90): ContentHealthData
    {
        $issues = [
            $this->makeIssue('scheduled_pages', __('capell-dashboard-reports::dashboard.issue_scheduled_pages'), $this->basePageQuery()->pending()->count()),
            $this->makeIssue('expired_pages', __('capell-dashboard-reports::dashboard.issue_expired_pages'), $this->basePageQuery()->expired()->count()),
            $this->makeIssue('pages_without_urls', __('capell-dashboard-reports::dashboard.issue_pages_without_urls'), $this->pagesWithoutUrlsCount()),
            $this->makeIssue('stale_pages', __('capell-dashboard-reports::dashboard.issue_stale_pages', ['days' => $staleDays]), $this->stalePagesCount($staleDays)),
        ];

        return new ContentHealthData(
            issues: ContentHealthIssueData::collect($issues, DataCollection::class),
        );
    }

    private function pagesWithoutUrlsCount(): int
    {
        return $this->basePageQuery()
            ->whereDoesntHave('pageUrls')
            ->count();
    }

    private function stalePagesCount(int $staleDays): int
    {
        return $this->basePageQuery()
            ->publishedDate()
            ->where((new Page)->qualifyColumn('updated_at'), '<', now()->subDays($staleDays))
            ->count();
    }

    private function makeIssue(string $id, string $label, int $count): ContentHealthIssueData
    {
        return new ContentHealthIssueData(
            id: $id,
            label: $label,
            count: $count,
            filterUrl: $this->pageIndexUrl(),
        );
    }

    private function pageIndexUrl(): ?string
    {
        try {
            return PageResource::getUrl('index');
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @return Builder<Page>
     */
    private function basePageQuery(): Builder
    {
        /** @var Builder<Page> $query */
        $query = SiteScope::applyForCurrentActor(Page::query());

        return $query;
    }
}
