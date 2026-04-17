<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Capell\Mosaic\Models\Content;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Content run(Content $content, array $data = [])
 */
class ReplicateContentAction
{
    use AsObject;

    public function handle(Content $content, array $data = []): Content
    {
        $content->load('translations');

        $translations = [];
        if (isset($data['translations'])) {
            $translations = $data['translations'];
            unset($data['translations']);
        }

        /** @var Content $className */
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
