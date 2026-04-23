<?php

declare(strict_types=1);

namespace Capell\Media\Filament\Resources\Media\Tables;

use Capell\Admin\Actions\ReplaceMediaFileAction;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters([
                SelectFilter::make('collection_name')
                    ->label(__('capell-admin::table.collection'))
                    ->options(
                        fn (): array => Media::query()
                            ->select('collection_name')
                            ->distinct()
                            ->orderBy('collection_name')
                            ->pluck('collection_name', 'collection_name')
                            ->all(),
                    ),
                SelectFilter::make('mime_group')
                    ->label(__('capell-admin::table.file_type'))
                    ->options(self::mimeGroups())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        if ($value === 'application/pdf') {
                            return $query->where('mime_type', $value);
                        }

                        return $query->where('mime_type', 'like', $value . '/%');
                    }),
                SelectFilter::make('model_type')
                    ->label(__('capell-admin::table.record_type'))
                    ->options(fn (): array => collect(Relation::morphMap())
                        ->filter(fn (mixed $class, string $alias): bool => is_string($class) && class_exists($class))
                        ->mapWithKeys(fn (string $class, string $alias): array => [$class => Str::headline(class_basename($class))])
                        ->sort()
                        ->all())
                    ->searchable(),
            ])
            ->recordUrl(fn (Media $record): ?string => self::getOwnerUrl($record))
            ->recordActions([
                EditAction::make()
                    ->url(fn (Media $record): ?string => self::getOwnerUrl($record)),
                Action::make('replace-file')
                    ->label(__('capell-media::media.replace_file'))
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->color('gray')
                    ->schema([
                        FileUpload::make('replacement')
                            ->label(__('capell-media::media.replacement_file'))
                            ->required(),
                    ])
                    ->action(function (Media $record, array $data): void {
                        // Filament FileUpload stores the file to the local disk and
                        // returns a disk-relative path. Convert it to an absolute path.
                        $diskRelativePath = is_array($data['replacement'] ?? null)
                            ? (string) array_values($data['replacement'])[0]
                            : (string) ($data['replacement'] ?? '');

                        if ($diskRelativePath === '') {
                            return;
                        }

                        $absolutePath = Storage::disk('local')->path($diskRelativePath);

                        ReplaceMediaFileAction::run($record, $absolutePath);

                        Notification::make()
                            ->title(__('capell-media::media.replace_file_success'))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            ImageColumn::make('original_url')
                ->label(__('capell-admin::table.image'))
                ->circular()
                ->extraImgAttributes(['loading' => 'lazy'])
                ->size(36)
                ->toggleable(),
            NameColumn::make('file_name')
                ->label(__('capell-admin::table.name'))
                ->searchable()
                ->copyable()
                ->copyMessage(__('copied')),
            TextColumn::make('collection_name')
                ->label(__('capell-admin::table.collection'))
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('disk')
                ->label(__('capell-admin::table.storage_disk'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('mime_type')
                ->label(__('capell-admin::table.file_type'))
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('size')
                ->label(__('capell-admin::table.size'))
                ->alignRight()
                ->numeric()
                ->sortable()
                ->formatStateUsing(fn (int|string|null $state): ?string => is_numeric($state) ? Number::fileSize((int) $state) : null)
                ->toggleable(),
            TextColumn::make('model_type')
                ->label(__('capell-admin::table.type'))
                ->formatStateUsing(fn (?string $state): ?string => $state !== null ? Str::of(class_basename($state))->headline()->toString() : null)
                ->toggleable(isToggledHiddenByDefault: true)
                ->toggleable(),
            TextColumn::make('model_id')
                ->label(__('capell-admin::table.record'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->alignCenter()
                ->toggleable(),
            TextColumn::make('owner_label')
                ->label(__('capell-admin::table.owner'))
                ->getStateUsing(fn (Media $record): ?string => self::getOwnerLabel($record))
                ->toggleable(),
            DateColumn::make('created_at'),
        ];
    }

    protected static function getOwnerLabel(Media $media): ?string
    {
        $model = $media->model;

        if ($model === null) {
            return null;
        }

        $type = class_basename($model::class);

        $name = method_exists($model, 'getAttribute') && $model->getAttribute('name') !== null && $model->getAttribute('name') !== ''
            ? (string) $model->getAttribute('name')
            : (method_exists($model, 'getAttribute') && $model->getAttribute('title') !== null && $model->getAttribute('title') !== '' ? (string) $model->getAttribute('title') : '#' . $model->getKey());

        return sprintf('%s — %s', Str::headline($type), $name);
    }

    protected static function getOwnerUrl(Media $media): ?string
    {
        $modelClass = is_string($media->model_type) ? (Relation::getMorphedModel($media->model_type) ?? $media->model_type) : null;

        if ($modelClass === null || ! class_exists($modelClass)) {
            return null;
        }

        $model = $media->model;

        if ($model === null) {
            return null;
        }

        $modelType = class_basename($model);

        // Try to get resource by model type first
        $resource = CapellAdmin::getResourceIfExists($modelType);

        // If model is a Page subtype, try Page resource with lowercase model name
        if ($resource === null && $model instanceof Page) {
            $resource = CapellAdmin::getResourceIfExists('Page', Str::lower($modelType));
        }

        if ($resource === null) {
            return null;
        }

        return $resource::getUrl('edit', ['record' => $model->getKey()]);
    }

    private static function mimeGroups(): array
    {
        return [
            'image' => 'Images',
            'video' => 'Video',
            'audio' => 'Audio',
            'application/pdf' => 'PDFs',
            'application' => 'Documents',
            'text' => 'Text',
        ];
    }
}
