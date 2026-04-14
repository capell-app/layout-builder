<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Contracts\HasPageResource;
use Capell\Admin\Filament\Resources\Pages\Tables\PagesTable;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

class PageAssets extends AbstractAssets implements HasPageResource
{
    public string $type = 'page';

    #[Locked]
    public string $tableConfiguration = PagesTable::class;

    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Page);
    }

    public function getFilteredTableQuery(): Builder
    {
        $query = parent::getFilteredTableQuery();

        if (isset($this->getTableFilterState('filter')['language_id'])) {
            $language_id = $this->getTableFilterState('filter')['language_id'];
        } else {
            /** @var class-string<Language> $model */
            $model = CapellCore::getModel(ModelEnum::Language);

            $language_id = $model::query()->default()->value('id');
        }

        $query->with([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language_id),
            'pageUrl' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language_id),
        ]);

        return $query;
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Core\Models\Page> $model */
        $model = CapellCore::getModel(ModelEnum::Page);

        return $model::with([
            'translations.language',
            'ancestors.type',
            'creator',
            'layout',
            'image',
            'media',
            'editor',
            'site.siteDomains',
            'type',
        ])
            ->when(
                $this->tableArguments['pageId'] ?? null,
                fn (BuilderContract $query): BuilderContract => $query->whereKeyNot($this->tableArguments['pageId']),
            )
            ->when(
                $this->existingRecords,
                fn (Builder $query) => $query->whereNotIn('id', $this->existingRecords),
            );
    }
}
