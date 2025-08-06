<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Core\Models\Site;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
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
        $this->contentModel = CapellCore::getModel(LayoutModelEnum::Content->name);
        $this->typeModel = CapellCore::getModel(ModelEnum::Type);
    }

    public function createContent(array $data, ?Site $site, Collection $languages): Content
    {
        if (! empty($data['type'])) {
            $type = $this->typeModel::query()
                ->where('type', LayoutTypeEnum::Content)
                ->where('key', $data['type'])
                ->first();
        } else {
            $type = $this->typeModel::query()
                ->where('type', LayoutTypeEnum::Content)
                ->default()
                ->first();
        }

        $meta = [];

        if (! empty($data['image_id'])) {
            $meta['image_id'] = $data['image_id'];
        }

        $content = $this->contentModel::firstOrCreate([
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
                'content' => $translation_data['contents'] ?? null,
                'meta' => $translation_data['meta'] ?? [],
            ]);
        }

        return $content;
    }
}
