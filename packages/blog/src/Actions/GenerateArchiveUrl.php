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
        return sprintf('%s/%d-%02d', $url->full_url, $date->year, $date->month);
    }
}
