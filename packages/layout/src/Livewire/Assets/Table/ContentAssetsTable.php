<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\ResourceEnum;
use Capell\Layout\Filament\Resources\Contents\Tables\ContentsTable;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

class ContentAssetsTable extends AbstractAssetsTable
{
    public string $type = 'content';

    #[Locked]
    public string $tableConfiguration = ContentsTable::class;

    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Content);
    }

    public function getFilteredTableQuery(): Builder
    {
        $query = parent::getFilteredTableQuery();

        if (isset($this->getTableFilterState('filter')['language_id'])) {
            $language_id = $this->getTableFilterState('filter')['language_id'];
        } else {
            $language_id = CapellCore::getModel(CoreModelEnum::Language)::query()->default()->value('id');
        }

        $query->with([
            'translation' => fn (BuilderContract $query) => $query->where('language_id', (int) $language_id),
        ]);

        return $query;
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Layout\Models\Content> $model */
        $model = CapellCore::getModel(ModelEnum::Content->name);

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
