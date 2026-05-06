<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Section;
use Illuminate\Support\Collection;

class ContentCreator
{
    /**
     * @var class-string<Section>
     */
    private readonly string $contentModel;

    /**
     * @var class-string<Type>
     */
    private readonly string $typeModel;

    public function __construct()
    {
        $this->contentModel = Section::class;

        $this->typeModel = Type::class;
    }

    public function createContent(array $data, ?Site $site, Collection $languages): Section
    {
        $type = $this->typeModel::query()->where('type', LayoutTypeEnum::Section)->default()->first();

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

        /** @var Section $content */
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
