<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Enums\LayoutTypeEnum;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
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
        $site = Site::getDefault();

        $data['type_id'] = $this->getDefaultType()->getKey();

        $data['translations'] = $site?->translations->mapWithKeys(fn (Translation $translation): array => [
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
        $model = Type::class;

        $contentType = $model::query()
            ->where('type', LayoutTypeEnum::ContentBlock)
            ->orderBy('default', 'desc')
            ->orderBy('id')
            ->first();

        throw_unless($contentType, Exception::class, 'No default content type found');

        return $contentType;
    }
}
