<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Actions\HintEditAction;
use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Layout\Actions\CreateContentAction;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Resources\ContentResource;
use Capell\Layout\Models\Content;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NestedSet;

class ContentSelect extends Forms\Components\Select
{
    use HasCustomSelectOption;

    private null|string|Closure $contentType = null;

    private ?Closure $modifySelectOptionsQueryUsing = null;

    private ?string $parentContentType = null;

    private bool $withUuid = false;

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
                    search: $search
                );
            })
            ->getOptionLabelUsing(fn (self $component, $value): ?string => $component->withUuid
                ? Content::findByUuid($value, ['name'])?->name
                : Content::find($value, ['name'])?->name)
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

    public function withCreateForm(): self
    {
        return $this->getOptionLabelFromRecordUsing(fn (Content $record): string => static::getSelectOption($record))
            ->createOptionForm(
                fn (mixed $state, Form $form): Form => $form->operation('createOption')
                    ->schema(ContentResource::getFormSchema($form))
                    ->model(Content::class)
            )
            ->createOptionUsing(function (ContentSelect $component, array $data): string {
                $content = CreateContentAction::run($data);

                Notification::make()
                    ->title(__('capell-admin::message.content_created_successfully'))
                    ->body($content->name)
                    ->send();

                return $content->{$component->getOptionKey()};
            })
            ->createOptionAction(
                fn (Forms\Components\Actions\Action $action): Forms\Components\Actions\Action => $action
                    ->modal()
                    ->modalHeading(__('capell-admin::generic.type'))
                    ->modalDescription(function (string $context, self $component, mixed $state): ?string {
                        if ($context !== 'create') {
                            return null;
                        }

                        if (! $state) {
                            return null;
                        }

                        return Str::title($component->getContentType());
                    })
                    ->fillForm(function (): array {
                        $site = Models\Site::default()->first();

                        /** @var class-string<Models\Type> $model */
                        $model = CapellCore::getModel(ModelEnum::Type);

                        return [
                            'type_id' => $model::query()
                                ->where('type', LayoutTypeEnum::Content)
                                ->default()
                                ->value('id'),
                            'translations' => $site->translations->mapWithKeys(fn ($translation) => [
                                (string) Str::uuid() => [
                                    'language_id' => $translation->language_id,
                                ],
                            ])->toArray(),
                        ];
                    })
                    ->modalWidth(MaxWidth::ScreenLarge)
                    ->visible(fn (mixed $state, $record): bool => ! $state)
                    ->successNotificationTitle(
                        fn (Forms\Components\Actions\Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $action->getModalHeading()]
                        )
                    )
                    ->after(function (Forms\Components\Actions\Action $action): void {
                        $action->success();
                    })
            );
    }

    public function withEditForm(): self
    {
        return $this->editOptionForm(function (mixed $state, Form $form): ?Form {
            if (! $state) {
                return $form;
            }

            return $form->operation('editOption')
                ->schema(ContentResource::getFormSchema($form));
        })
            ->editOptionAction(fn (Forms\Components\Actions\Action $action): Forms\Components\Actions\Action => $action
                ->modalHeading(
                    fn (self $component): string => __(
                        'capell-admin::heading.edit_content_record',
                        ['name' => $component->getSelectedRecord()->name]
                    )
                )
                ->modalWidth(MaxWidth::ScreenExtraLarge)
                ->visible(fn (mixed $state): bool => (bool) $state)
                ->successNotificationTitle(
                    fn (Forms\Components\Actions\Action $action): string => __(
                        'capell-admin::notification.updated_successfully',
                        ['name' => $action->getModalHeading()]
                    )
                )
                ->after(function (Forms\Components\Actions\Action $action): void {
                    $action->success();
                })
            )
            ->fillEditOptionActionFormUsing(static function (self $component): array {
                /** @var Content $record */
                $record = $component->getSelectedRecord();

                return $record?->attributesToArray() ?? [];
            })
            ->getSelectedRecordUsing(
                static fn (self $component, $state): ?Model => $component->withUuid
                    ? Content::findByUuid($state)
                    : Content::find($state)
            )
            ->updateOptionUsing(static function (array $data, Form $form): void {
                $form->getRecord()?->update($data);
            });
    }

    public function withHintEditAction(): static
    {
        return $this->hintAction(
            fn ($state, $operation): HintEditAction => HintEditAction::make('edit-content')
                ->visible(fn (): bool => $operation !== 'create' && filled($state))
                ->url(function () use ($state): string {
                    if (! $state) {
                        return '';
                    }

                    return ContentResource::getUrl('edit', ['record' => $state]);
                })
        );
    }

    public function withUuid(bool $withUuid = true): static
    {
        $this->withUuid = $withUuid;

        return $this;
    }

    private function getContentOptionLabel(Content $content, ?int $siteId): string
    {
        $label = '';

        if (($siteId === null || $siteId === 0) && $content->site) {
            $label .= $content->site->name.' » ';
        }

        if ($content->ancestors->isNotEmpty()) {
            $label .= $content->ancestors->pluck('name')
                ->map(fn ($item) => Str::limit($item, 30))
                ->implode(' » ')
                .' » ';
        }

        return $label.Str::limit($content->name, 40);
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
        $model = CapellCore::getModel(LayoutModelEnum::Content->name);

        /** @var \Kalnoy\Nestedset\Collection $content */
        $contents = $model::select('contents.*')
            ->with($relations)
            ->withDrafts()
            ->join('types', 'contents.type_id', '=', 'types.id')
            ->when(
                $this->modifySelectOptionsQueryUsing instanceof Closure,
                fn (Builder $query): mixed => $this->evaluate($this->modifySelectOptionsQueryUsing, [
                    'query' => $query,
                    'record' => $this->getRecord(),
                ])
            )
            ->when(
                $contentType,
                fn (Builder $query) => $query->whereHas('type', fn (BuilderContract $query) => $query->where('key', $contentType))
            )
            ->when(
                $site_id,
                fn (Builder $query) => $query->where('site_id', $site_id)
            )
            ->when(
                $parentContentType,
                fn (Builder $query) => $query->whereHas(
                    'parent.type',
                    fn (BuilderContract $query) => $query->where('key', $parentContentType)
                )
            )
            ->when(
                $search,
                fn (Builder $query, $search) => $query->where('contents.name', 'like', sprintf('%%%s%%', $search))
                    ->orderByRaw('CASE WHEN contents.name = ? THEN 1 ELSE 0 END DESC, INSTR(contents.name, ?), contents.name', [$search, $search]),
                fn (Builder $query) => $query->limit(10)
            )
            ->orderBy('site_id')
            ->orderBy(NestedSet::LFT, 'ASC')
            ->get();

        return $contents->mapWithKeys(
            fn (Content $content): array => [$content->{$this->getOptionKey()} => $this->getContentOptionLabel($content, $site_id)]
        )
            ->toArray();
    }

    private function getOptionKey(): string
    {
        return $this->withUuid ? 'uuid' : 'id';
    }
}
