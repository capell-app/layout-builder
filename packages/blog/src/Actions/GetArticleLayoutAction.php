<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Blog\Enums\BlogLayoutEnum;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static ?Layout run()
 */
class GetArticleLayoutAction
{
    use AsObject;

    public function handle(): ?Layout
    {
        /** @var class-string<Layout> $model */
        $model = CapellCore::getModel(ModelEnum::Layout);

        return $model::query()->firstWhere('key', BlogLayoutEnum::Article->value);
    }
}
