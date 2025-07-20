<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Awcodes\Curator\Components\Forms\Uploader;
use Awcodes\Curator\Resources\Media\Schemas\MediaForm;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaSchema
{
    public static function make(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    Group::make()
                        ->columnSpan([
                            'md' => 'full',
                            'lg' => 2,
                        ])
                        ->schema([
                            MediaForm::getUploaderField()
                                ->afterStateUpdated(function (Set $set, Get $get, Uploader $component, ?TemporaryUploadedFile $state): void {
                                    if (! $state instanceof TemporaryUploadedFile || $get('alt')) {
                                        return;
                                    }

                                    $name = pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME);
                                    $set('alt', str($name)->replace(['-', '_'], ' ')->headline()->toString());
                                }),
                        ]),
                    Group::make()
                        ->schema([
                            Section::make(trans('curator::forms.sections.meta'))
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
            TextInput::make('alt')
                ->label(trans('curator::forms.fields.alt'))
                ->hint(fn (): HtmlString => new HtmlString('<a href="https://www.w3.org/WAI/tutorials/images/decision-tree" class="filament-link text-primary-500" target="_blank">'.trans('curator::forms.fields.alt_hint').'</a>')),
            TextInput::make('title')
                ->label(trans('curator::forms.fields.title')),
            Textarea::make('caption')
                ->label(trans('curator::forms.fields.caption'))
                ->rows(2),
            Textarea::make('description')
                ->label(trans('curator::forms.fields.description'))
                ->rows(2),
        ];
    }
}
