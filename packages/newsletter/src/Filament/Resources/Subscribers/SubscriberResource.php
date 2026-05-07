<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Resources\Subscribers;

use BackedEnum;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Enums\SubscriberStatus;
use Capell\Newsletter\Filament\Concerns\ScopesNewsletterResourcesToAssignedSites;
use Capell\Newsletter\Filament\Resources\Subscribers\Pages\CreateSubscriber;
use Capell\Newsletter\Filament\Resources\Subscribers\Pages\EditSubscriber;
use Capell\Newsletter\Filament\Resources\Subscribers\Pages\ListSubscribers;
use Capell\Newsletter\Models\Subscriber;
use Capell\Newsletter\Providers\NewsletterServiceProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class SubscriberResource extends Resource
{
    use ScopesNewsletterResourcesToAssignedSites;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $recordTitleAttribute = 'email';

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return $configurator->components([
            SiteSelect::make('site_id')->required(),
            TextInput::make('email')
                ->label(__('capell-newsletter::form.email'))
                ->email()
                ->required(),
            TextInput::make('first_name')
                ->label(__('capell-newsletter::form.first_name')),
            TextInput::make('last_name')
                ->label(__('capell-newsletter::form.last_name')),
            Select::make('status')
                ->label(__('capell-newsletter::form.status'))
                ->options(self::subscriberStatusOptions())
                ->required(),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('capell-newsletter::table.id'))->sortable(),
                TextColumn::make('email')->label(__('capell-newsletter::table.email')),
                TextColumn::make('status')->label(__('capell-newsletter::table.status'))->badge()->sortable(),
                TextColumn::make('subscribed_at')->label(__('capell-newsletter::table.subscribed_at'))->dateTime()->sortable(),
                TextColumn::make('created_at')->label(__('capell-newsletter::table.created_at'))->dateTime()->sortable(),
            ])
            ->filters([
                Filter::make('email')
                    ->form([
                        TextInput::make('email')
                            ->label(__('capell-newsletter::table.email'))
                            ->email(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $email = $data['email'] ?? null;

                        return is_string($email) && trim($email) !== ''
                            ? $query->where('email_hash', Subscriber::emailHash($email))
                            : $query;
                    }),
            ]);
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return self::applyNewsletterSiteScope(parent::getEloquentQuery());
    }

    #[Override]
    public static function getModel(): string
    {
        return Subscriber::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-newsletter::navigation.subscribers');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscribers::route('/'),
            'create' => CreateSubscriber::route('/create'),
            'edit' => EditSubscriber::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function subscriberStatusOptions(): array
    {
        return collect(SubscriberStatus::cases())
            ->mapWithKeys(static fn (SubscriberStatus $status): array => [$status->value => $status->getLabel()])
            ->all();
    }
}
