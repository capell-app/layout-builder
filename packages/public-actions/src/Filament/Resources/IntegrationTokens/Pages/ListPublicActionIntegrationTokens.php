<?php

declare(strict_types=1);

namespace Capell\PublicActions\Filament\Resources\IntegrationTokens\Pages;

use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\PublicActions\Actions\CreatePublicActionIntegrationTokenAction;
use Capell\PublicActions\Enums\PublicActionIntegrationProvider;
use Capell\PublicActions\Enums\PublicActionIntegrationTokenAbility;
use Capell\PublicActions\Filament\Resources\IntegrationTokens\PublicActionIntegrationTokenResource;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

final class ListPublicActionIntegrationTokens extends ListRecords
{
    protected static string $resource = PublicActionIntegrationTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createToken')
                ->label(__('capell-public-actions::filament.actions.create_token'))
                ->form([
                    TextInput::make('name')
                        ->label(__('capell-public-actions::filament.fields.name'))
                        ->required(),
                    Select::make('provider')
                        ->label(__('capell-public-actions::filament.fields.provider'))
                        ->options([
                            PublicActionIntegrationProvider::Zapier->value => PublicActionIntegrationProvider::Zapier->getLabel(),
                            PublicActionIntegrationProvider::Api->value => PublicActionIntegrationProvider::Api->getLabel(),
                        ])
                        ->default(PublicActionIntegrationProvider::Zapier->value)
                        ->required(),
                    SiteSelect::make('site_id')
                        ->label(__('capell-public-actions::filament.fields.site'))
                        ->preload(),
                    CheckboxList::make('abilities')
                        ->label(__('capell-public-actions::filament.fields.abilities'))
                        ->options([
                            PublicActionIntegrationTokenAbility::ListActions->value => PublicActionIntegrationTokenAbility::ListActions->getLabel(),
                            PublicActionIntegrationTokenAbility::SubmitActions->value => PublicActionIntegrationTokenAbility::SubmitActions->getLabel(),
                            PublicActionIntegrationTokenAbility::ReadSubmissions->value => PublicActionIntegrationTokenAbility::ReadSubmissions->getLabel(),
                        ])
                        ->default(array_map(
                            static fn (PublicActionIntegrationTokenAbility $ability): string => $ability->value,
                            PublicActionIntegrationTokenAbility::cases(),
                        )),
                ])
                ->action(function (array $data): void {
                    $created = CreatePublicActionIntegrationTokenAction::run(
                        name: (string) $data['name'],
                        provider: PublicActionIntegrationProvider::from((string) $data['provider']),
                        siteId: filled($data['site_id'] ?? null) ? (int) $data['site_id'] : null,
                        abilities: collect($data['abilities'] ?? [])
                            ->filter(fn (mixed $ability): bool => is_string($ability))
                            ->map(fn (string $ability): PublicActionIntegrationTokenAbility => PublicActionIntegrationTokenAbility::from($ability))
                            ->values()
                            ->all(),
                    );

                    Notification::make()
                        ->title(__('capell-public-actions::filament.notifications.token_created_title'))
                        ->body(__('capell-public-actions::filament.notifications.token_created_body', ['token' => $created->plainTextToken]))
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
