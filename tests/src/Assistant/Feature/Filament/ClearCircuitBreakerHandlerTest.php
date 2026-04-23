<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Admin\Support\AdminEventRegistry;
use Capell\Core\Models\Page;
use Capell\SeoTools\Handlers\ClearCircuitBreakerHandler;
use Capell\SeoTools\Support\PrismProvider;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Notifications\Notification;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('dispatches clear-circuit-breaker event and calls handler', function (): void {
    $mockProvider = new class extends PrismProvider
    {
        public bool $resetCalled = false;

        public function resetCircuitBreaker(): void
        {
            $this->resetCalled = true;
        }
    };
    app()->instance(PrismProvider::class, $mockProvider);

    // Bind a shared AdminEventRegistry and register the event
    $registry = new AdminEventRegistry;
    $registry->register(
        EditPage::class,
        'clear-circuit-breaker',
        ClearCircuitBreakerHandler::class,
    );
    app()->instance(AdminEventRegistry::class, $registry);

    $record = Page::factory()->create();

    $livewire = livewire(EditPage::class, ['record' => $record->getKey()]);

    // Invade to protected getListeners method
    $instance = $livewire->instance();
    $ref = new ReflectionClass($instance);
    $method = $ref->getMethod('getListeners');

    $listeners = $method->invoke($instance);
    expect($listeners)->toHaveKey('clear-circuit-breaker');
    expect($listeners['clear-circuit-breaker'])->toBe('routeAdminEvent');

    $livewire->dispatch('clear-circuit-breaker', 'clear-circuit-breaker')
        ->assertOk();

    expect($mockProvider->resetCalled)->toBeTrue();
    Notification::assertNotified();
});
