<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Exception;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array run(array $data = [])
 */
class MutateContentDataBeforeFillAction
{
    use AsObject;

    public function handle(array $data = []): array
    {
        $site = Site::query()->default()->first();

        $data['is_published'] = true;

        $data['type_id'] = $this->getDefaultType()->getKey();

        $data['translations'] = $site?->translations->mapWithKeys(fn ($translation): array => [
            (string) Str::uuid() => [
                'language_id' => $translation->language_id,
            ],
        ])
            ->all();

        return $data;
    }

    private function getDefaultType(): Type
    {
        /** @var class-string<Type> $model */
        $model = CapellCore::getModel(ModelEnum::Type);

        $contentType = $model::query()
            ->where('type', LayoutTypeEnum::Content)
            ->orderBy('default', 'desc')
            ->orderBy('id')
            ->first();

        throw_unless($contentType, Exception::class, 'No default content type found');

        return $contentType;
    }
}
