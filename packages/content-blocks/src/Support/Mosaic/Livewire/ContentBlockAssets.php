<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Support\Mosaic\Livewire;

use Capell\Admin\Facades\CapellAdmin;
use Capell\ContentBlocks\Enums\ResourceEnum;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\Tables\ContentBlocksTable;
use Capell\ContentBlocks\Models\ContentBlock;
use Capell\Core\Models\Language;
use Capell\Mosaic\Livewire\Assets\Table\AbstractAssets;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

class ContentBlockAssets extends AbstractAssets
{
    public string $type = 'content_block';

    #[Locked]
    public string $tableConfiguration = ContentBlocksTable::class;

    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::ContentBlock);
    }

    public function getFilteredTableQuery(): Builder
    {
        $query = parent::getFilteredTableQuery();

        if (isset($this->getTableFilterState('filter')['language_id'])) {
            $language_id = $this->getTableFilterState('filter')['language_id'];
        } else {
            /** @var class-string<Language> $model */
            $model = Language::class;

            $language_id = $model::query()->default()->value('id');
        }

        $query->with([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language_id),
        ]);

        return $query;
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\ContentBlocks\Models\ContentBlock> $model */
        $model = ContentBlock::class;

        return $model::with([
            'ancestors.type',
            'creator',
            'editor',
            'image',
            'media',
            'site',
            'translations.language',
            'type',
        ])
            ->when(
                $this->existingRecords,
                fn (Builder $query) => $query->whereNotIn('id', $this->existingRecords),
            );
    }
}
