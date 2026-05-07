<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Resources\FormMappings;

use BackedEnum;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Enums\ConfirmationMode;
use Capell\Newsletter\Filament\Concerns\ScopesNewsletterResourcesToAssignedSites;
use Capell\Newsletter\Filament\Resources\FormMappings\Pages\CreateFormMapping;
use Capell\Newsletter\Filament\Resources\FormMappings\Pages\EditFormMapping;
use Capell\Newsletter\Filament\Resources\FormMappings\Pages\ListFormMappings;
use Capell\Newsletter\Models\FormMapping;
use Capell\Newsletter\Providers\NewsletterServiceProvider;
use Capell\Tags\Models\Tag;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class FormMappingResource extends Resource
{
    use ScopesNewsletterResourcesToAssignedSites;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return $configurator->components([
            SiteSelect::make('site_id')->required(),
            Select::make('form_id')->relationship('form', 'name'),
            TextInput::make('name')->label(__('capell-newsletter::form.name'))->required(),
            TextInput::make('form_handle')->label(__('capell-newsletter::form.form_handle')),
            TextInput::make('email_field')->label(__('capell-newsletter::form.email_field'))->required(),
            TextInput::make('first_name_field')->label(__('capell-newsletter::form.first_name_field')),
            TextInput::make('last_name_field')->label(__('capell-newsletter::form.last_name_field')),
            TextInput::make('consent_field')->label(__('capell-newsletter::form.consent_field')),
            TextInput::make('consent_text')->label(__('capell-newsletter::form.consent_text')),
            TextInput::make('consent_version')->label(__('capell-newsletter::form.consent_version')),
            Select::make('fixed_tag_ids')
                ->multiple()
                ->options(fn (): array => self::newsletterTagOptions()),
            KeyValue::make('field_tag_mappings'),
            Toggle::make('requires_double_opt_in')->label(__('capell-newsletter::form.requires_double_opt_in'))->default(true),
            Select::make('confirmation_mode')
                ->label(__('capell-newsletter::form.confirmation_mode'))
                ->options(self::confirmationModeOptions())
                ->default(ConfirmationMode::CapellOwned->value)
                ->required(),
            Toggle::make('is_active')->label(__('capell-newsletter::form.active'))->default(true),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('capell-newsletter::form.name'))->searchable()->sortable(),
            TextColumn::make('form_handle')->label(__('capell-newsletter::form.form_handle'))->searchable(),
            TextColumn::make('email_field')->label(__('capell-newsletter::form.email_field')),
            TextColumn::make('updated_at')->label(__('capell-newsletter::table.updated_at'))->dateTime()->sortable(),
        ]);
    }

    #[Override]
    public static function getModel(): string
    {
        return FormMapping::class;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return self::applyNewsletterSiteScope(parent::getEloquentQuery());
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Marketing';
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-newsletter::navigation.form_mappings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormMappings::route('/'),
            'create' => CreateFormMapping::route('/create'),
            'edit' => EditFormMapping::route('/{record}/edit'),
        ];
    }

    private static function confirmationModeOptions(): array
    {
        return collect(ConfirmationMode::cases())
            ->mapWithKeys(static fn (ConfirmationMode $mode): array => [$mode->value => $mode->getLabel()])
            ->all();
    }

    private static function newsletterTagOptions(): array
    {
        return Tag::query()
            ->where('type', config('capell-newsletter.newsletter_tag_type', 'newsletter'))
            ->get()
            ->mapWithKeys(static function (Tag $tag): array {
                $name = $tag->getAttribute('name');
                $fallbackName = is_array($name) ? reset($name) : null;
                $label = is_array($name)
                    ? (string) ($name[app()->getLocale()] ?? (is_scalar($fallbackName) ? $fallbackName : ''))
                    : (string) $name;

                return [(string) $tag->getKey() => $label];
            })
            ->all();
    }
}
