<?php

declare(strict_types=1);

namespace Capell\MediaAI\Filament;

use Capell\Admin\Contracts\Extenders\MediaEditActionExtender;
use Capell\Admin\Filament\Resources\Media\Pages\EditMedia;
use Capell\Core\Models\Media;
use Capell\MediaAI\Contracts\ImageDoctor;
use Capell\MediaAI\Data\ImageDoctorRequest;
use Capell\MediaAI\Support\NullImageDoctor;
use Filament\Actions\Action;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\Textarea;
use Filament\Notifications\Notification;

final class MediaAIEditActionExtender implements MediaEditActionExtender
{
    public function getHeaderActions(EditMedia $page): array
    {
        return [
            Action::make('doctor-image')
                ->label(__('capell-media-ai::media-ai.doctor_image'))
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->modalDescription(__('capell-media-ai::media-ai.doctor_image_description'))
                ->schema([
                    Select::make('operation')
                        ->label(__('capell-media-ai::media-ai.operation'))
                        ->options(__('capell-media-ai::media-ai.operations'))
                        ->default('improve')
                        ->required(),
                    Textarea::make('instructions')
                        ->label(__('capell-media-ai::media-ai.instructions'))
                        ->placeholder(__('capell-media-ai::media-ai.instructions_placeholder'))
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
                        ->title($result->message ?? __('capell-media-ai::media-ai.success'));

                    $result->successful
                        ? $notification->success()
                        : $notification->warning();

                    $notification->send();
                }),
        ];
    }
}
