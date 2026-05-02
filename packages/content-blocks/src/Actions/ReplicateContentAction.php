<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Models\ContentBlock;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static ContentBlock run(ContentBlock $content, array $data = [])
 */
class ReplicateContentAction
{
    use AsObject;

    public function handle(ContentBlock $content, array $data = []): ContentBlock
    {
        $content->load('translations');

        $translations = [];
        if (isset($data['translations'])) {
            $translations = $data['translations'];
            unset($data['translations']);
        }

        /** @var class-string<ContentBlock> $className */
        $className = $content::class;

        $model = $className::query()->find($content->getKey());

        $model->fill($data);

        $replica = $model->replicate();

        $replica->created_at = now();
        $replica->updated_at = now();

        $replica->save();

        if ($translations) {
            foreach ($translations as $translation) {
                $replica->translations()->create($translation);
            }

            $replica->load('translations');
        }

        return $replica;
    }
}
