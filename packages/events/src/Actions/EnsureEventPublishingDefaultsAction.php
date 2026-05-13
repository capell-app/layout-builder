<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Admin\Filament\Configurators\Pages\ResultsPageConfigurator;
use Capell\Admin\Filament\Configurators\Types\PageTypeConfigurator;
use Capell\Core\Actions\GetOrCreateResultsLayoutAction;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Enums\UrlParamTypeEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Type;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\Events\Enums\LivewireComponentEnum;
use Filament\Support\Icons\Heroicon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static void run()
 */
class EnsureEventPublishingDefaultsAction
{
    use AsAction;

    public function handle(): void
    {
        $this->eventPageType();
        $this->eventsListingPageType();
        $this->defaultEventLayout();
        $this->eventsListingLayout();
    }

    public function eventPageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => 'event',
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-events::generic.event'),
            'group' => 'Event',
            'admin' => [
                'type_configurator' => PageTypeConfigurator::getKey(),
                'icon' => 'heroicon-' . Heroicon::OutlinedCalendarDays->value,
                'required_fields' => ['title'],
                'resource' => 'event',
            ],
            'meta' => [
                'schema' => ['type' => 'Event'],
                'sitemap' => true,
                'url_params' => ['date' => UrlParamTypeEnum::String->value],
                'with_date' => true,
                'with_image' => true,
            ],
        ]);
    }

    public function eventsListingPageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => 'events',
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-events::generic.events'),
            'group' => 'Event',
            'admin' => [
                'type_configurator' => PageTypeConfigurator::getKey(),
                'configurator' => ResultsPageConfigurator::getKey(),
                'icon' => 'heroicon-' . Heroicon::OutlinedCalendarDays->value,
                'required_fields' => ['title'],
            ],
            'meta' => [
                'component' => LivewireComponentEnum::EventsCalendarPage->value,
                'livewire' => true,
                'limit' => 24,
                'pagination' => true,
                'sitemap' => true,
            ],
        ]);
    }

    public function defaultEventLayout(): Layout
    {
        return Layout::query()->firstWhere('key', 'event')
            ?? resolve(LayoutCreator::class)->create('event');
    }

    public function eventsListingLayout(): Layout
    {
        return GetOrCreateResultsLayoutAction::run();
    }
}
