<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Core\Models\PageUrl;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static string run(PageUrl $url, ArchiveMonthData $date)
 */
class GenerateArchiveUrl
{
    use AsObject;

    public function handle(PageUrl $url, ArchiveMonthData $date): string
    {
        $archiveDate = sprintf('%d-%02d', $date->year, $date->month);

        if (str_contains($url->full_url, '*')) {
            return str_replace('*', $archiveDate, $url->full_url);
        }

        return sprintf('%s/%s', $url->full_url, $archiveDate);
    }
}
