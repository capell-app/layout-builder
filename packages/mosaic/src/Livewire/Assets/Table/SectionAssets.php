<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Assets\Table;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Enums\ResourceEnum;
use Capell\Mosaic\Filament\Resources\Sections\Tables\SectionsTable;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

class SectionAssets extends AbstractAssets
{
    public string $type = 'section';

    #[Locked]
    public string $tableConfiguration = SectionsTable::class;

    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Section);
    }

    public function getFilteredTableQuery(): Builder
    {
        $query = parent::getFilteredTableQuery();

        if (isset($this->getTableFilterState('filter')['language_id'])) {
            $language_id = $this->getTableFilterState('filter')['language_id'];
        } else {
            /** @var class-string<Language> $model */
            $model = CapellCore::getModel(CoreModelEnum::Language);

            $language_id = $model::query()->default()->value('id');
        }

        $query->with([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language_id),
        ]);

        return $query;
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Mosaic\Models\Section> $model */
        $model = CapellCore::getModel(ModelEnum::Section->name);

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
