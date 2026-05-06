<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Assets\Table;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Models\Language;
use Capell\LayoutBuilder\Enums\ResourceEnum;
use Capell\LayoutBuilder\Filament\Resources\Sections\Tables\SectionsTable;
use Capell\LayoutBuilder\Models\Section;
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
        return AdminSurfaceLookup::resource(ResourceEnum::Section);
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
        /* @var class-string<\Capell\LayoutBuilder\Models\Section> $model */
        $model = Section::class;

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
