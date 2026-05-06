<?php

declare(strict_types=1);

namespace Capell\ContentSections\Actions;

use Capell\ContentSections\Models\Section;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Section run(Section $content, array $data = [])
 */
class ReplicateContentAction
{
    use AsObject;

    public function handle(Section $content, array $data = []): Section
    {
        $content->load('translations');

        $translations = [];
        if (isset($data['translations'])) {
            $translations = $data['translations'];
            unset($data['translations']);
        }

        /** @var class-string<Section> $className */
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
