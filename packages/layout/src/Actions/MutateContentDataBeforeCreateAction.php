<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Exception;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array run(array $data = [])
 */
class MutateContentDataBeforeCreateAction
{
    use AsObject;

    public function handle(array $data = []): array
    {
        $data['type_id'] = $this->getDefaultType()->getKey();

        return $data;
    }

    private function getDefaultType(): Models\Type
    {
        /** @var class-string<Models\Type> $model */
        $model = CapellCore::getModel(ModelEnum::Type);

        $contentType = $model::contentType()
            ->orderBy('default', 'desc')
            ->orderBy('id')
            ->first();

        if (! $contentType) {
            throw new Exception('No default content type found');
        }

        return $contentType;
    }
}
