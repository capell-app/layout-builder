<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\HintEditAction;
use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Models\Content;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\Collection;
use Kalnoy\Nestedset\NestedSet;

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
            ->getSearchResultsUsing(function (self $component, $record, Get $get, string $search): array {
                $site_id = $get('site_id');

                return $component->getContentOptions(
                    site_id: $site_id,
                    search: $search,
                );
            })
            ->getOptionLabelUsing(fn (self $component, ?int $value): ?string => Content::query()->find($value, ['name'])?->name)
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
        $asset = CapellCore::getAsset(ModelEnum::Content->name);

        $adminAsset = CapellAdmin::getAsset(ModelEnum::Content);

        $createOptionUsing = $this->getCreateOptionUsing();

        return $this->createOptionAction(
            fn (Action $action): Action => $this->modifyCreateAction($action)
                ->fillForm(fn (): array => in_array($adminAsset->defaultDataAction, [null, '', '0'], true) ? [] : $adminAsset->defaultDataAction::run()),
        )
            ->createOptionForm(
                fn (Schema $schema): Schema => $adminAsset->formClass::configure(
                    $schema->operation('createOption')->model($asset->model),
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
            ->getOptionLabelFromRecordUsing(fn (Content $record): string => static::getSelectOption($record));
    }

    public function withEditForm(): self
    {
        $asset = CapellAdmin::getAsset(ModelEnum::Content);

        return $this->editOptionForm(function (mixed $state, Schema $schema) use ($asset): Schema {
            if (! $state) {
                return $schema;
            }

            return $asset->formClass::configure($schema->operation('editOption'));
        })
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(
                        fn (self $component): string => __(
                            'capell-layout::heading.edit_content_record',
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
                /** @var Content $record */
                $record = $component->getSelectedRecord();

                return $record?->attributesToArray() ?? [];
            })
            ->getSelectedRecordUsing(static fn (self $component, $state): ?Model => Content::query()->find($state))
            ->updateOptionUsing(static function (array $data, Schema $schema): void {
                $schema->getRecord()->update($data);
            });
    }

    public function withHintEditAction(): static
    {
        return $this->hintAction(
            fn ($state, $operation): HintEditAction => HintEditAction::make('edit-content')
                ->visible(fn (): bool => $operation !== 'create' && filled($state))
                ->url(function () use ($state): string {
                    if ($state === []) {
                        return '';
                    }

                    return ContentResource::getUrl('edit', ['record' => $state]);
                }),
        );
    }

    private function getContentOptionLabel(Content $record, ?int $siteId): HtmlString
    {
        $label = '';

        if (($siteId === null || $siteId === 0) && $record->site) {
            $label .= $record->site->name . ' &raquo; ';
        }

        $ancestors = $record->ancestors()->get();

        if ($ancestors->isNotEmpty()) {
            $label .= $ancestors->pluck('name')
                ->map(fn ($item) => Str::limit($item, 30))
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

        /** @var class-string<Content> $model */
        $model = CapellCore::getModel(ModelEnum::Content->name);

        /** @var Collection $content */
        $contents = $model::query()->select('contents.*')
            ->with($relations)
            ->withDrafts()
            ->join('types', 'contents.type_id', '=', 'types.id')
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
                fn (Builder $query, string $search) => $query->where('contents.name', 'like', sprintf('%%%s%%', $search))
                    ->orderByRaw('CASE WHEN contents.name = ? THEN 1 ELSE 0 END DESC, INSTR(contents.name, ?), contents.name', [$search, $search]),
                fn (Builder $query) => $query->limit(10),
            )
            ->orderBy('site_id')
            ->orderBy(NestedSet::LFT, 'ASC')
            ->get();

        return $contents->mapWithKeys(
            fn (Content $content): array => [$content->getKey() => $this->getContentOptionLabel($content, $site_id)],
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
