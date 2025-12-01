<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Core\Models\Site;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Models\Content;
use Illuminate\Database\Eloquent\Collection;

class ContentCreator
{
    /**
     * @var class-string<Content>
     */
    private readonly string $contentModel;

    /**
     * @var class-string<Models\Type>
     */
    private readonly string $typeModel;

    public function __construct()
    {
        $this->contentModel = CapellCore::getModel(ModelEnum::Content->name);

        $this->typeModel = CapellCore::getModel(CoreModelEnum::Type);
    }

    public function createContent(array $data, ?Site $site, Collection $languages): Content
    {
        $type = $this->typeModel::query()->where('type', LayoutTypeEnum::Content)->default()->first();

        if (! empty($data['type'])) {
            $type->where('key', $data['type'])->first();
        } else {
            $type->default()->first();
        }

        $meta = [];

        $content = $this->contentModel::query()->firstOrCreate([
            'name' => $data['name'],
            'site_id' => $site?->id,
            'type_id' => $type->id,
            'parent_id' => $data['parent_id'] ?? null,
        ], [
            'meta' => $meta !== [] ? $meta : null,
        ]);

        foreach ($languages as $language) {
            $translation_data = $data['translations'][$language->code];

            $content->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => $translation_data['title'],
                'content' => $translation_data['content'] ?? null,
                'meta' => $translation_data['meta'] ?? [],
            ]);
        }

        return $content;
    }
}
