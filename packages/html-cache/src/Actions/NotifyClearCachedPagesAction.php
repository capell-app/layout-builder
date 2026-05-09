<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Actions;

use Capell\HtmlCache\Models\CachedModelUrl;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Collection $models)
 */
final class NotifyClearCachedPagesAction
{
    use AsFake;
    use AsObject;

    public function handle(Collection $models): void
    {
        $cachedUrls = $models
            ->filter(fn (mixed $model): bool => $model instanceof Model)
            ->flatMap(fn (Model $model): array => CachedModelUrl::query()
                ->where('cacheable_type', $model->getMorphClass())
                ->where('cacheable_id', (int) $model->getKey())
                ->pluck('url')
                ->all())
            ->unique()
            ->values();

        if ($cachedUrls->isEmpty()) {
            return;
        }

        if (config('capell-admin.auto_clear_cache') === true) {
            ClearCachedPageUrlsAction::run($cachedUrls);

            return;
        }

        Notification::make('clear-page-cache')
            ->title(__('capell-admin::notification.detected_cached_pages', ['count' => $cachedUrls->count()]))
            ->body(fn (): HtmlString => new HtmlString(
                $cachedUrls
                    ->slice(0, 3)
                    ->map(fn (string $url): string => '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer" class="hover:underline break-all line-clamp-1">' . e($url) . '</a>')
                    ->join(' '),
            ))
            ->icon('heroicon-c-exclamation-triangle')
            ->iconColor('warning')
            ->actions([
                Action::make('clear')
                    ->label(__('capell-admin::button.refresh_cache'))
                    ->badge((string) $cachedUrls->count())
                    ->color('warning')
                    ->icon(Heroicon::ArrowPath)
                    ->link()
                    ->dispatch('refresh-cache', [$cachedUrls]),
            ])
            ->persistent()
            ->send();
    }
}
