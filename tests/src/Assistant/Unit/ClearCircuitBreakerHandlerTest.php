<?php

declare(strict_types=1);

use Capell\Admin\Support\AdminEventRegistry;
use Capell\Admin\Support\AdminEventRouter;
use Capell\SeoTools\Handlers\ClearCircuitBreakerHandler;
use Capell\SeoTools\Support\PrismProvider;
use Capell\Tests\Assistant\Fixtures\HandlerDummyComponent;
use Filament\Notifications\Notification;

it('registers clear-circuit-breaker handler for EditPage and executes', function (): void {
    // Use the global app container to align with resolve()
    $app = app();

    $mockProvider = new class extends PrismProvider
    {
        public bool $resetCalled = false;

        public function resetCircuitBreaker(): void
        {
            $this->resetCalled = true;
        }
    };
    $app->instance(PrismProvider::class, $mockProvider);

    $app->bind(ClearCircuitBreakerHandler::class, fn (): ClearCircuitBreakerHandler => new ClearCircuitBreakerHandler);

    $router = new AdminEventRouter($app, $app->make(AdminEventRegistry::class));

    // Register event mapping for the dummy component used in this unit test
    $app->make(AdminEventRegistry::class)
        ->register(HandlerDummyComponent::class, 'clear-circuit-breaker', ClearCircuitBreakerHandler::class);

    $component = new HandlerDummyComponent;
    $router->handle('clear-circuit-breaker', [], $component);

    expect($mockProvider->resetCalled)->toBeTrue();
    Notification::assertNotified();
});
