<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Resources\Events;

use BackedEnum;
use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Events\Actions\EnsureEventPublishingDefaultsAction;
use Capell\Events\Enums\ResourceEnum;
use Capell\Events\Filament\Resources\Events\Pages\CreateEvent;
use Capell\Events\Filament\Resources\Events\Pages\EditEvent;
use Capell\Events\Filament\Resources\Events\Pages\ListEvents;
use Capell\Events\Filament\Resources\Events\Schemas\EventForm;
use Capell\Events\Filament\Resources\Events\Tables\EventsTable;
use Capell\Events\Models\Event;
use Capell\Events\Providers\EventsServiceProvider;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class EventResource extends PageResource
{
    protected static string $adminResourceName = ResourceEnum::Event->name;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::CalendarDays;

    protected static ?string $slug = 'event';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = EventForm::class;

    protected static string $tableConfigurator = EventsTable::class;

    /**
     * @return class-string<Event>
     */
    public static function getModel(): string
    {
        return Event::class;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_content');
    }

    public static function getResourceType(): ConfiguratorTypeEnum
    {
        return ConfiguratorTypeEnum::Page;
    }

    public static function getBasePath(Site $site, Language $language): string
    {
        return '/events/';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedCalendarDays;
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::CalendarDays;
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-events::generic.events');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(EventsServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array &$data, array $formData = []): void
    {
        parent::mutateFormDataBeforeCreate($data, $formData);

        $defaults = resolve(EnsureEventPublishingDefaultsAction::class);

        if (! isset($data['type_id']) || $data['type_id'] === null || $data['type_id'] === '') {
            $data['type_id'] = $defaults->eventPageType()->getKey();
        }

        if (! isset($data['layout_id']) || $data['layout_id'] === null || $data['layout_id'] === '') {
            $data['layout_id'] = $defaults->defaultEventLayout()->getKey();
        }
    }

    public static function applyTypeAdminResourceConstraint(BuilderContract $query, ?bool $hideSystemPages = false): void
    {
        $query->where('key', 'event');
    }

    public static function getResourceName(): ?string
    {
        return strtolower(ResourceEnum::Event->name);
    }
}
