<?php

declare(strict_types=1);

namespace Capell\AccessGate\Filament\Resources\AccessAreas\Pages;

use Capell\AccessGate\Actions\ApproveNextRegistrationsAction;
use Capell\AccessGate\Actions\UpdateAccessGateApprovalLimitAction;
use Capell\AccessGate\Actions\UpdateAccessGateAreaStatusAction;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Filament\Resources\AccessAreas\AccessAreaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

final class EditAccessArea extends EditRecord
{
    protected static string $resource = AccessAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pause')
                ->label(__('capell-access-gate::filament.actions.pause'))
                ->visible(fn (): bool => $this->record->status === AccessAreaStatus::Active)
                ->action(fn (): mixed => UpdateAccessGateAreaStatusAction::run(
                    $this->record,
                    AccessAreaStatus::Paused,
                    updatedByUserId: auth()->id(),
                )),
            Action::make('resume')
                ->label(__('capell-access-gate::filament.actions.resume'))
                ->visible(fn (): bool => $this->record->status === AccessAreaStatus::Paused)
                ->action(fn (): mixed => UpdateAccessGateAreaStatusAction::run(
                    $this->record,
                    AccessAreaStatus::Active,
                    updatedByUserId: auth()->id(),
                )),
            Action::make('approveNext')
                ->label(__('capell-access-gate::filament.actions.approve_next'))
                ->form([
                    TextInput::make('count')
                        ->label(__('capell-access-gate::filament.fields.count'))
                        ->numeric()
                        ->minValue(1)
                        ->default(10)
                        ->required(),
                ])
                ->action(fn (array $data): mixed => ApproveNextRegistrationsAction::run(
                    $this->record,
                    (int) $data['count'],
                    approvedByUserId: auth()->id(),
                )),
            Action::make('updateApprovalLimit')
                ->label(__('capell-access-gate::filament.actions.update_approval_limit'))
                ->form([
                    TextInput::make('approval_limit')
                        ->label(__('capell-access-gate::filament.fields.approval_limit'))
                        ->numeric()
                        ->minValue(0)
                        ->default($this->record->approval_limit),
                ])
                ->action(fn (array $data): mixed => UpdateAccessGateApprovalLimitAction::run(
                    $this->record,
                    isset($data['approval_limit']) && $data['approval_limit'] !== '' ? (int) $data['approval_limit'] : null,
                    updatedByUserId: auth()->id(),
                )),
            DeleteAction::make(),
        ];
    }
}
