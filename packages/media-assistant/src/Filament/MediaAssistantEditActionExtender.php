<?php

declare(strict_types=1);

namespace Capell\MediaAssistant\Filament;

use Capell\Admin\Contracts\Extenders\MediaEditActionExtender;
use Capell\Admin\Filament\Resources\Media\Pages\EditMedia;
use Capell\Core\Models\Media;
use Capell\MediaAssistant\Contracts\ImageDoctor;
use Capell\MediaAssistant\Data\ImageDoctorRequest;
use Capell\MediaAssistant\Support\NullImageDoctor;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

final class MediaAssistantEditActionExtender implements MediaEditActionExtender
{
    public function getHeaderActions(EditMedia $page): array
    {
        return [
            Action::make('doctor-image')
                ->label(__('capell-media-assistant::media-assistant.doctor_image'))
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->modalDescription(__('capell-media-assistant::media-assistant.doctor_image_description'))
                ->schema([
                    Select::make('operation')
                        ->label(__('capell-media-assistant::media-assistant.operation'))
                        ->options(__('capell-media-assistant::media-assistant.operations'))
                        ->default('improve')
                        ->required(),
                    Textarea::make('instructions')
                        ->label(__('capell-media-assistant::media-assistant.instructions'))
                        ->placeholder(__('capell-media-assistant::media-assistant.instructions_placeholder'))
                        ->rows(4)
                        ->required(),
                ])
                ->visible(fn (Media $record): bool => $record->isImage() && ! resolve(ImageDoctor::class) instanceof NullImageDoctor)
                ->action(function (Media $record, array $data): void {
                    $result = resolve(ImageDoctor::class)->doctor(
                        $record,
                        new ImageDoctorRequest(
                            operation: (string) $data['operation'],
                            instructions: (string) $data['instructions'],
                        ),
                    );

                    $notification = Notification::make()
                        ->title($result->message ?? __('capell-media-assistant::media-assistant.success'));

                    $result->successful
                        ? $notification->success()
                        : $notification->warning();

                    $notification->send();
                }),
        ];
    }
}
