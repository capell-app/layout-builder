<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

class ContentCreator
{
    /**
     * @var class-string<Model>
     */
    private readonly string $contentModel;

    /**
     * @var class-string<Blueprint>
     */
    private readonly string $typeModel;

    public function __construct()
    {
        throw_unless(CapellCore::hasAsset('Section'), RuntimeException::class, 'Content Sections must be installed to create section demo content.');

        $contentModel = CapellCore::getAsset('Section')->model;

        throw_unless(is_subclass_of($contentModel, Model::class), RuntimeException::class, 'Section asset model must be an Eloquent model.');

        /** @var class-string<Model> $contentModel */
        $this->contentModel = $contentModel;

        $this->typeModel = Blueprint::class;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     * @param  array<array-key, mixed>  $data
     */
    public function createContent(array $data, ?Site $site, Collection $languages): Model
    {
        $typeQuery = $this->typeModel::query()->where('type', 'section');
        $type = isset($data['type']) && $data['type'] !== ''
            ? (clone $typeQuery)->where('key', $data['type'])->first()
            : (clone $typeQuery)->default()->first();

        throw_unless($type instanceof Blueprint, RuntimeException::class, 'A content blueprint is required to create demo content.');

        $parentId = $data['parent_id'] ?? null;

        $payload = [
            'name' => $data['name'],
            'site_id' => $site?->id,
            'blueprint_id' => $type->id,
            'parent_id' => $parentId,
        ];

        $content = $this->contentModel::query()->firstOrCreate($payload);

        foreach ($languages as $language) {
            $code = $language->getAttribute('code');

            if (! is_string($code)) {
                continue;
            }

            $translationData = $this->translationDataFor($data['translations'] ?? [], $code);

            Translation::query()->firstOrCreate([
                'translatable_type' => $content->getMorphClass(),
                'translatable_id' => $content->getKey(),
                'language_id' => $language->getKey(),
            ], [
                'title' => $translationData['title'],
                'content' => $translationData['content'] ?? null,
                'meta' => $translationData['meta'] ?? [],
            ]);
        }

        return $content;
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, mixed>
     */
    private function translationDataFor(array $translations, string $code): array
    {
        $translationData = $translations[$code] ?? $translations['en'] ?? reset($translations);

        throw_unless(is_array($translationData), RuntimeException::class, 'Content demo data must include at least one translation.');

        return $translationData;
    }
}
