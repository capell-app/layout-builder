<?php

declare(strict_types=1);

namespace Capell\Assistant\Filament\Actions;

use Capell\Assistant\Actions\GenerateAiImageAction;
use Capell\Assistant\DataObjects\AiImageData;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Throwable;

class AiImageGeneratorAction extends Action
{
    /**
     * @param  array<string, string>  $contextFieldKeys  Keys of sibling Filament fields to read as context
     */
    public static function make(?string $name = null, array $contextFieldKeys = []): static
    {
        return parent::make($name ?? 'generate-ai-image')
            ->label('Generate with AI')
            ->icon('heroicon-o-sparkles')
            ->modalHeading('AI Image Generator')
            ->modalSubmitActionLabel('Accept')
            ->form(function (Get $get) use ($contextFieldKeys): array {
                $contextParts = [];
                foreach ($contextFieldKeys as $key => $label) {
                    $value = $get($key);
                    if (filled($value)) {
                        $contextParts[] = "{$label}: {$value}";
                    }
                }
                $autoPrompt = implode('. ', $contextParts);

                return [
                    Textarea::make('prompt')
                        ->label('Describe the image')
                        ->default($autoPrompt)
                        ->required()
                        ->rows(3)
                        ->helperText('Edit to refine, then click Generate.'),

                    Actions::make([
                        Action::make('generate_preview')
                            ->label('Generate')
                            ->color('gray')
                            ->action(function (array $state, Set $set): void {
                                try {
                                    $data = new AiImageData(
                                        prompt: $state['prompt'],
                                        size: (string) config('capell-assistant.prism.image_size', '1024x1024'),
                                    );

                                    $url = app(GenerateAiImageAction::class)->handle($data);
                                    $set('preview_url', $url);
                                } catch (Throwable $e) {
                                    Notification::make()
                                        ->title('Image generation failed')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),

                    ViewField::make('preview_url')
                        ->view('capell-assistant::filament.fields.image-preview')
                        ->visible(fn (Get $get): bool => filled($get('preview_url'))),
                ];
            })
            ->action(function (array $data, Set $set) use ($name): void {
                $url = $data['preview_url'] ?? null;

                if (! $url) {
                    Notification::make()
                        ->title('No image generated yet')
                        ->warning()
                        ->send();

                    return;
                }

                $set('../../' . $name, $url);

                Notification::make()
                    ->title('Image applied')
                    ->success()
                    ->send();
            });
    }
}
