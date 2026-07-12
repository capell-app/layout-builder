<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Enums\LayoutBuilderEditorMode;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Support\LayoutBuilderConfiguration;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait AuthorizesLayoutBuilderAccess
{
    public function canEditContent(): bool
    {
        return $this->canPerformLayoutBuilderAbility('editContent');
    }

    public function canEditLayout(): bool
    {
        return $this->canPerformLayoutBuilderAbility('editLayout');
    }

    public function assertCanUpdateLayout(): void
    {
        $this->assertCanEditLayout();
    }

    public function assertCanEditContent(): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);

        $this->assertLayoutMatchesPageSite();

        if ($this->page instanceof Model) {
            $this->authorizeLayoutBuilderAbility($actor, 'editContent', $this->page);

            return;
        }

        $this->authorizeLayoutBuilderAbility($actor, 'editContent', $this->layout);
    }

    protected function assertCanUseLayoutBuilder(): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);

        $this->assertLayoutMatchesPageSite();

        if ($this->canEditContent() || $this->canEditLayout()) {
            return;
        }

        $this->assertCanEditLayout();
    }

    protected function assertCanEditLayout(): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);

        $this->assertLayoutMatchesPageSite();

        if ($this->page instanceof Model) {
            $this->authorizeLayoutBuilderAbility($actor, 'editLayout', $this->page);
        }

        $this->authorizeLayoutBuilderAbility($actor, 'editLayout', $this->layout);
    }

    protected function assertCanCreateLayoutPreset(Site $site): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);

        Gate::forUser($actor)->authorize('create', [LayoutPreset::class, $site]);
    }

    protected function assertCanApplyLayoutPreset(LayoutPreset $preset, Site $site): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);

        Gate::forUser($actor)->authorize('apply', [$preset, $site]);
    }

    protected function assertCanUpdateLayoutPreset(LayoutPreset $preset, ?Site $site): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);
        throw_unless($site instanceof Site, AuthorizationException::class);

        Gate::forUser($actor)->authorize('update', [$preset, $site]);
    }

    protected function assertLayoutMatchesPageSite(): void
    {
        if ($this->site instanceof Site) {
            throw_if($this->layout->hasAttribute('site_id')
            && $this->layout->site_id !== null
            && (int) $this->layout->site_id !== (int) $this->site->getKey(), AuthorizationException::class);
            throw_if($this->page instanceof Model
            && $this->page->hasAttribute('site_id')
            && $this->page->site_id !== null
            && (int) $this->page->site_id !== (int) $this->site->getKey(), AuthorizationException::class);
        }

        if (! $this->page instanceof Model) {
            return;
        }

        if (! $this->layout->hasAttribute('site_id') || $this->layout->site_id === null) {
            return;
        }

        if (! $this->page->hasAttribute('site_id') || $this->page->site_id === $this->layout->site_id) {
            return;
        }

        throw new AuthorizationException;
    }

    private function resolveInitialEditorMode(): string
    {
        $configuredMode = LayoutBuilderEditorMode::fromConfig(
            $this->configuredDefaultEditorMode(),
        );

        if ($configuredMode === LayoutBuilderEditorMode::ContentFirst && $this->canEditContent()) {
            return $configuredMode->value;
        }

        if ($configuredMode === LayoutBuilderEditorMode::LayoutFirst && $this->canEditLayout()) {
            return $configuredMode->value;
        }

        if ($this->canEditContent()) {
            return LayoutBuilderEditorMode::ContentFirst->value;
        }

        if ($this->canEditLayout()) {
            return LayoutBuilderEditorMode::LayoutFirst->value;
        }

        return $configuredMode->value;
    }

    private function configuredDefaultEditorMode(): string
    {
        $configuration = LayoutBuilderConfiguration::class;

        if (class_exists($configuration)) {
            return $configuration::defaultEditorMode();
        }

        $packageMode = config('capell-layout-builder.editor_mode.default');

        if (is_string($packageMode) && $packageMode !== '') {
            return $packageMode;
        }

        $legacyPackageMode = config('capell-layout-builder.editor_mode');

        if (is_string($legacyPackageMode) && $legacyPackageMode !== '') {
            return $legacyPackageMode;
        }

        $adminMode = config('capell-admin.layout_builder.default_editor_mode');

        return is_string($adminMode) && $adminMode !== ''
            ? $adminMode
            : LayoutBuilderEditorMode::ContentFirst->value;
    }

    private function canPerformLayoutBuilderAbility(string $ability): bool
    {
        $actor = Filament::auth()->user();

        if ($actor === null) {
            return false;
        }

        if ($ability === 'editLayout' && $this->page instanceof Model) {
            return $this->allowsLayoutBuilderAbility($actor, $ability, $this->page)
                && $this->allowsLayoutBuilderAbility($actor, $ability, $this->layout);
        }

        $record = $this->page instanceof Model ? $this->page : $this->layout;

        return $this->allowsLayoutBuilderAbility($actor, $ability, $record);
    }

    private function allowsLayoutBuilderAbility(Authenticatable $actor, string $ability, Model $record): bool
    {
        $policy = Gate::getPolicyFor($record);

        if ($policy === null) {
            return true;
        }

        $resolvedAbility = method_exists($policy, $ability) ? $ability : 'update';

        return Gate::forUser($actor)->allows($resolvedAbility, $record);
    }

    private function authorizeLayoutBuilderAbility(Authenticatable $actor, string $ability, Model $record): void
    {
        $policy = Gate::getPolicyFor($record);

        if ($policy === null) {
            return;
        }

        $resolvedAbility = method_exists($policy, $ability) ? $ability : 'update';

        Gate::forUser($actor)->authorize($resolvedAbility, $record);
    }
}
