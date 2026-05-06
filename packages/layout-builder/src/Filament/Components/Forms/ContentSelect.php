<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Aimeos\Nestedset\NestedSet;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Models\Section;
use Closure;
use Filament\Actions\Action;
use Filament\FormBuilder\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ContentSelect extends Select
{
    use HasCustomSelectOption;

    private null|string|Closure $contentType = null;

    private ?Closure $modifySelectOptionsQueryUsing = null;

    private ?string $parentContentType = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.content'))
            ->searchable()
            ->preload()
            ->optionsLimit(100)
            ->allowHtml()
            ->getSearchResultsUsing(function (self $component, Get $get, string $search): array {
                $site_id = $get('site_id');

                return $component->getContentOptions(
                    site_id: $site_id,
                    search: $search,
                );
            })
            ->getOptionLabelUsing(fn (self $component, ?int $value): ?string => Section::query()->find($value, ['name'])?->name)
            ->options(fn (self $component): array => $component->getContentOptions());
    }

    public function contentType(string|Closure $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->evaluate($this->contentType);
    }

    public function modifySelectOptionsQueryUsing(?Closure $callback): static
    {
        $this->modifySelectOptionsQueryUsing = $callback;

        return $this;
    }

    public function parentContentType(string $contentType): self
    {
        $this->parentContentType = $contentType;

        return $this;
    }

    public function withCreateForm(): Select
    {
        $asset = CapellCore::getAsset('Section');

        $adminAsset = CapellAdmin::getAsset('Section');

        $createOptionUsing = $this->getCreateOptionUsing();

        return $this->createOptionAction(
            fn (Action $action): Action => $this->modifyCreateAction($action)
                ->fillForm(fn (): array => in_array($adminAsset->defaultDataAction, [null, '', '0'], true) ? [] : $adminAsset->defaultDataAction::run()),
        )
            ->createOptionForm(
                fn (Schema $configurator): Schema => $adminAsset->formClass::configure(
                    $configurator->operation('createOption')->model($asset->model),
                ),
            )
            ->createOptionUsing(function (Select $component, array $data) use ($asset, $adminAsset, $createOptionUsing): int|string {
                $record = in_array($adminAsset->createAction, [null, '', '0'], true)
                    ? $component->evaluate($createOptionUsing)
                    : $adminAsset->createAction::run($data);

                Notification::make()
                    ->title(__('capell-admin::message.asset_created_successfully', ['name' => $asset->name]))
                    ->body($record->name)
                    ->send();

                return $record->getKey();
            })
            ->getOptionLabelFromRecordUsing(fn (Section $record): string => static::getSelectOption($record));
    }

    public function withEditForm(): self
    {
        $asset = CapellAdmin::getAsset('Section');

        return $this->editOptionForm(function (?int $state, Schema $configurator) use ($asset): Schema {
            if ($state === null) {
                return $configurator;
            }

            return $asset->formClass::configure($configurator->operation('editOption'));
        })
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(
                        fn (self $component): string => __(
                            'capell-layout-builder::heading.edit_content_record',
                            ['name' => $component->getSelectedRecord()->name],
                        ),
                    )
                    ->modalWidth(Width::ScreenExtraLarge)
                    ->visible(fn (mixed $state): bool => (bool) $state)
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.updated_successfully',
                            ['name' => $action->getModalHeading()],
                        ),
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    }),
            )
            ->fillEditOptionActionFormUsing(static function (self $component): array {
                /** @var Section $record */
                $record = $component->getSelectedRecord();

                return $record?->attributesToArray() ?? [];
            })
            ->getSelectedRecordUsing(static fn (?int $state): ?Section => Section::query()->find($state))
            ->updateOptionUsing(static function (array $data, Schema $configurator): void {
                $configurator->getRecord()->update($data);
            });
    }

    private function getContentOptionLabel(Section $record, ?int $siteId): HtmlString
    {
        $label = '';

        if (($siteId === null || $siteId === 0) && $record->site) {
            $label .= $record->site->name . ' &raquo; ';
        }

        $ancestors = $record->ancestors()->get();

        if ($ancestors->isNotEmpty()) {
            $label .= $ancestors->pluck('name')
                ->map(fn (string $name): string => Str::limit($name, 30))
                ->implode(' &raquo; ')
                . ' &raquo; ';
        }

        return new HtmlString($label . Str::limit($record->name, 40));
    }

    private function getContentOptions(?int $site_id = null, ?string $search = null): array
    {
        $relations = ['ancestors'];
        if ($site_id === null || $site_id === 0) {
            $relations[] = 'site';
        }

        $contentType = $this->getContentType();

        $parentContentType = $this->parentContentType;

        /** @var class-string<Section> $model */
        $model = Section::class;

        /** @var Section $content */
        $contents = $model::query()->select('sections.*')
            ->with($relations)
            ->join('types', 'sections.type_id', '=', 'types.id')
            ->when(
                $this->modifySelectOptionsQueryUsing instanceof Closure,
                fn (Builder $query): mixed => $this->evaluate($this->modifySelectOptionsQueryUsing, [
                    'query' => $query,
                    'record' => $this->getRecord(),
                ]),
            )
            ->when(
                $contentType,
                fn (Builder $query) => $query->whereHas('type', fn (BuilderContract $query): BuilderContract => $query->where('key', $contentType)),
            )
            ->when(
                $site_id,
                fn (Builder $query) => $query->where('site_id', $site_id),
            )
            ->when(
                $parentContentType,
                fn (Builder $query) => $query->whereHas(
                    'parent.type',
                    fn (BuilderContract $query): BuilderContract => $query->where('key', $parentContentType),
                ),
            )
            ->when(
                $search,
                fn (Builder $query, string $search) => $query->where('sections.name', 'like', sprintf('%%%s%%', $search))
                    ->orderByRaw('CASE WHEN sections.name = ? THEN 1 ELSE 0 END DESC, INSTR(sections.name, ?), sections.name', [$search, $search]),
                fn (Builder $query) => $query->limit(10),
            )
            ->orderBy('site_id')
            ->orderBy(NestedSet::LFT, 'ASC')
            ->get();

        return $contents->mapWithKeys(
            fn (Section $content): array => [$content->getKey() => $this->getContentOptionLabel($content, $site_id)],
        )
            ->toArray();
    }

    private function modifyCreateAction(Action $action): Action
    {
        return $action->slideOver()
            ->modalWidth(Width::ScreenLarge)
            ->closeModalByClickingAway(false)
            ->successNotificationTitle(
                fn (Action $action): string => __(
                    'capell-admin::notification.created_successfully',
                    ['name' => $action->getModalHeading()],
                ),
            )
            ->after(function (Action $action): void {
                $action->success();
            });
    }
}
