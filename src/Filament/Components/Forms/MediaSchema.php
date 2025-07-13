<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Awcodes\Curator\Components\Forms\Uploader;
use Capell\Admin\Filament\Resources\MediaResource;
use Filament\Forms;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaSchema
{
    public static function make(): array
    {
        return [
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Group::make()
                        ->columnSpan([
                            'md' => 'full',
                            'lg' => 2,
                        ])
                        ->schema([
                            MediaResource::getUploaderField()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, Uploader $component, ?TemporaryUploadedFile $state): void {
                                    if (! $state instanceof TemporaryUploadedFile || $get('alt')) {
                                        return;
                                    }

                                    $name = pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME);
                                    $set('alt', str($name)->replace(['-', '_'], ' ')->headline()->toString());
                                }),
                        ]),
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make(trans('curator::forms.sections.meta'))
                                ->schema(
                                    static::getAdditionalInformationFormSchema()
                                ),
                        ])->columnSpan([
                            'md' => 'full',
                            'lg' => 1,
                        ]),
                ]),
        ];
    }

    private static function getAdditionalInformationFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('alt')
                ->label(trans('curator::forms.fields.alt'))
                ->hint(fn (): HtmlString => new HtmlString('<a href="https://www.w3.org/WAI/tutorials/images/decision-tree" class="filament-link text-primary-500" target="_blank">'.trans('curator::forms.fields.alt_hint').'</a>')),
            Forms\Components\TextInput::make('title')
                ->label(trans('curator::forms.fields.title')),
            Forms\Components\Textarea::make('caption')
                ->label(trans('curator::forms.fields.caption'))
                ->rows(2),
            Forms\Components\Textarea::make('description')
                ->label(trans('curator::forms.fields.description'))
                ->rows(2),
        ];
    }
}
