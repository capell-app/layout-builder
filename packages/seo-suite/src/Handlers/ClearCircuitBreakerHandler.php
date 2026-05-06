<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Handlers;

use Capell\Admin\Support\AdminEventHandlerInterface;
use Capell\SeoSuite\Support\PrismProvider;
use Filament\Notifications\Notification;
use Livewire\Component;

final class ClearCircuitBreakerHandler implements AdminEventHandlerInterface
{
    /** @param array<int, mixed> $payload */
    public function handle(array $payload, Component $component): void
    {
        resolve(PrismProvider::class)->resetCircuitBreaker();

        Notification::make('circuit-breaker-cleared')
            ->title(__('capell-seo-suite::message.circuit_breaker_cleared'))
            ->success()
            ->send();
    }
}
