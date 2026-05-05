<?php

declare(strict_types=1);

namespace Capell\Migrator\Filament\Resources\ImportSessions\Pages;

use Capell\Migrator\Actions\CancelImportSessionAction;
use Capell\Migrator\Actions\RetryImportSessionAction;
use Capell\Migrator\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\Migrator\Filament\Resources\ImportSessions\Schemas\ImportSessionInfolist;
use Capell\Migrator\Models\ImportSession;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Override;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * @property-read ImportSession $record
 */
class ViewImportSession extends ViewRecord
{
    /** @return class-string<ImportSessionResource> */
    #[Override]
    public static function getResource(): string
    {
        return ImportSessionResource::class;
    }

    #[Override]
    public function infolist(Schema $schema): Schema
    {
        return ImportSessionInfolist::configure($schema);
    }

    #[Override]
    public function getSubNavigation(): array
    {
        return ImportSessionResource::getSubNavigation();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->downloadArchiveAction(),
            $this->cancelAction(),
            $this->retryAction(),
        ];
    }

    private function downloadArchiveAction(): Action
    {
        return Action::make('downloadArchive')
            ->label(__('capell-admin::exchanger.download_archive'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->visible(fn (): bool => $this->archiveExists($this->record))
            ->action(function (): StreamedResponse {
                $session = $this->record;
                $disk = $this->archiveDiskName();
                $filename = $session->source_filename ?? basename((string) $session->source_package_path);

                /** @var StreamedResponse $response */
                $response = Storage::disk($disk)->download((string) $session->source_package_path, $filename);

                return $response;
            });
    }

    private function cancelAction(): Action
    {
        return Action::make('cancelSession')
            ->label(__('capell-admin::exchanger.cancel_session'))
            ->icon('heroicon-o-x-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('capell-admin::exchanger.cancel_session_confirm_title'))
            ->modalDescription(__('capell-admin::exchanger.cancel_session_confirm_body'))
            ->visible(fn (): bool => (auth()->user()?->can('import-session.cancel') ?? false)
                && CancelImportSessionAction::isCancellable($this->record))
            ->action(function (): void {
                try {
                    CancelImportSessionAction::run($this->record);
                } catch (RuntimeException $runtimeException) {
                    Notification::make()
                        ->danger()
                        ->title(__('capell-admin::exchanger.cancel_failed'))
                        ->body($runtimeException->getMessage())
                        ->send();

                    return;
                }

                $this->refreshFormData(['status']);
                $this->record->refresh();

                Notification::make()
                    ->success()
                    ->title(__('capell-admin::exchanger.cancel_success'))
                    ->send();
            });
    }

    private function retryAction(): Action
    {
        return Action::make('retrySession')
            ->label(__('capell-admin::exchanger.retry_session'))
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(__('capell-admin::exchanger.retry_session_confirm_title'))
            ->modalDescription(__('capell-admin::exchanger.retry_session_confirm_body'))
            ->visible(fn (): bool => (auth()->user()?->can('import-session.retry') ?? false)
                && RetryImportSessionAction::canRetry($this->record))
            ->action(function (): void {
                try {
                    RetryImportSessionAction::run($this->record);
                } catch (Throwable $throwable) {
                    Notification::make()
                        ->danger()
                        ->title(__('capell-admin::exchanger.retry_failed'))
                        ->body($throwable->getMessage())
                        ->send();

                    return;
                }

                $this->record->refresh();

                Notification::make()
                    ->success()
                    ->title(__('capell-admin::exchanger.retry_success'))
                    ->send();
            });
    }

    private function archiveExists(ImportSession $session): bool
    {
        $path = (string) $session->source_package_path;
        if ($path === '') {
            return false;
        }

        return Storage::disk($this->archiveDiskName())->exists($path);
    }

    private function archiveDiskName(): string
    {
        $diskName = config('migrator.disk', 'local');

        return is_string($diskName) ? $diskName : 'local';
    }
}
