<?php

declare(strict_types=1);

namespace Capell\Mosaic\Support\Creator;

use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Core\Models\Site;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Models\Content;
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

        if (isset($data['type']) && $data['type'] !== '') {
            $type->where('key', $data['type'])->first();
        } else {
            $type->default()->first();
        }

        $parentId = $data['parent_id'] ?? null;

        $payload = [
            'name' => $data['name'],
            'site_id' => $site?->id,
            'type_id' => $type->id,
            'parent_id' => $parentId,
        ];

        /** @var Content $content */
        $content = $this->contentModel::query()->firstOrCreate($payload);

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
