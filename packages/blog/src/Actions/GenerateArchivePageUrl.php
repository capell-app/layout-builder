<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Core\Models\PageUrl;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static string run(PageUrl $pageUrl, ArchiveMonthData $date)
 */
class GenerateArchivePageUrl
{
    use AsObject;

    public function handle(PageUrl $pageUrl, ArchiveMonthData $date): string
    {
        return $pageUrl->full_url . '/' . $date->year . '-' . $date->month;
    }
}
