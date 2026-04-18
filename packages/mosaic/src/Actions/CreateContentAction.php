<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Models\Section;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Section run(array $data)
 */
class CreateContentAction
{
    use AsObject;

    public function createTranslations(Section $content, array $translations): void
    {
        foreach ($translations as $translation) {
            $content->translations()->create([
                'language_id' => $translation['language_id'],
                'title' => $translation['title'],
                'content' => $translation['content'],
            ]);
        }
    }

    public function handle(array $data): Section
    {
        /** @var class-string<Section> $model */
        $model = CapellCore::getModel(ModelEnum::Section->name);

        if (! isset($data['name']) && blank($data['name']) && isset($data['translations'])) {
            $data['name'] = collect($data['translations'])->first()['title'];
        }

        $content = $model::create($data);

        if (isset($data['translations'])) {
            $this->createTranslations($content, $data['translations']);
        }

        return $content;
    }
}
