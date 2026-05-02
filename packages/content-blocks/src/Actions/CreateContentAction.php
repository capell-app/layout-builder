<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Models\ContentBlock;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static ContentBlock run(array $data)
 */
class CreateContentAction
{
    use AsObject;

    public function createTranslations(ContentBlock $content, array $translations): void
    {
        foreach ($translations as $translation) {
            $content->translations()->create([
                'language_id' => $translation['language_id'],
                'title' => $translation['title'],
                'content' => $translation['content'],
            ]);
        }
    }

    public function handle(array $data): ContentBlock
    {
        $translations = $data['translations'] ?? [];
        unset($data['translations']);

        if (! isset($data['name']) && isset($translations[0])) {
            $data['name'] = $translations[0]['title'];
        }

        $content = ContentBlock::query()->create($data);

        if ($translations !== []) {
            $this->createTranslations($content, $translations);
        }

        return $content;
    }
}
