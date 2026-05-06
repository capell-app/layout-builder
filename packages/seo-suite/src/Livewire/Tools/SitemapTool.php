<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Livewire\Tools;

use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\GenerateSitemapAction;
use Capell\SeoSuite\Enums\SitemapCacheKey;
use Capell\SeoSuite\Support\Sitemap\XmlSitemapGenerator;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class SitemapTool extends Component
{
    public function generate(): void
    {
        $this->assertGlobalAdmin();

        /** @var class-string<Site> $model */
        $model = Site::class;
        $sites = $model::with(['siteDomains'])->enabled()->ordered()->get();

        if ($sites->isEmpty()) {
            return;
        }

        $this->deleteAllSitemaps($sites);

        Cache::put(SitemapCacheKey::Generating->value, $sites->count(), now()->addMinutes(60));

        foreach ($sites as $site) {
            GenerateSitemapAction::dispatch($site);
        }

        Notification::make('sitemap_queue')
            ->status('warning')
            ->title(__('capell-admin::message.sitemap_queue'))
            ->body(__('capell-admin::message.sitemap_info'))
            ->send();

        $this->dispatch('close-dropdown', id: 'admin-tools-dropdown');
    }

    public function render(): View
    {
        return view('capell-seo-suite::livewire.tools.sitemap-tool');
    }

    private function deleteAllSitemaps(Collection $sites): void
    {
        $sites->each(function (Site $site): void {
            resolve(XmlSitemapGenerator::class)->delete($site);
        });
    }

    private function assertGlobalAdmin(): void
    {
        $user = Filament::auth()->user();

        throw_if($user === null, AuthenticationException::class);

        if (method_exists($user, 'isGlobalAdmin') && $user->isGlobalAdmin()) {
            return;
        }

        $configured = config('filament-shield.super_admin.name', 'super_admin');
        $superAdminRole = is_string($configured) && $configured !== '' ? $configured : 'super_admin';

        if (method_exists($user, 'hasRole') && $user->hasRole($superAdminRole)) {
            return;
        }

        throw new AuthorizationException;
    }
}
