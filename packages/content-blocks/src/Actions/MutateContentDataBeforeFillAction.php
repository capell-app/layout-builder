<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
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

        $data['type_id'] = ResolveRequestedContentBlockTypeAction::run($data)?->getKey()
            ?? ResolveRequestedContentBlockTypeAction::make()->defaultType()->getKey();

        $data['translations'] = $site?->translations->mapWithKeys(fn (Translation $translation): array => [
            (string) Str::uuid() => [
                'language_id' => $translation->language_id,
            ],
        ])
            ->all();

        return $data;
    }
}
