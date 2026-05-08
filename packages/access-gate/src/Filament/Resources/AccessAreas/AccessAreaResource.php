<?php

declare(strict_types=1);

namespace Capell\AccessGate\Filament\Resources\AccessAreas;

use BackedEnum;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\ApprovalStrategy;
use Capell\AccessGate\Enums\IdentityMode;
use Capell\AccessGate\Enums\RegistrationPolicy;
use Capell\AccessGate\Enums\TokenPolicy;
use Capell\AccessGate\Filament\Resources\AccessAreas\Pages\CreateAccessArea;
use Capell\AccessGate\Filament\Resources\AccessAreas\Pages\EditAccessArea;
use Capell\AccessGate\Filament\Resources\AccessAreas\Pages\ListAccessAreas;
use Capell\AccessGate\Filament\Resources\Concerns\AccessGateFilamentOptions;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Providers\AccessGateServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Override;

final class AccessAreaResource extends Resource
{
    use AccessGateFilamentOptions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::LockClosed;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->schema([
                TextInput::make('key')
                    ->label(__('capell-access-gate::filament.fields.key'))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label(__('capell-access-gate::filament.fields.name'))
                    ->required(),
                Select::make('site_id')
                    ->label(__('capell-access-gate::filament.fields.site'))
                    ->helperText(__('capell-access-gate::filament.fields.site_help'))
                    ->options(fn (): array => self::canScopeToSites() ? Site::getOptions()->all() : [])
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => self::canScopeToSites()),
                Select::make('status')
                    ->label(__('capell-access-gate::filament.fields.status'))
                    ->options(self::enumOptions(AccessAreaStatus::class, 'capell-access-gate::filament.area_status'))
                    ->required(),
                Select::make('identity_mode')
                    ->label(__('capell-access-gate::filament.fields.identity_mode'))
                    ->options(self::enumOptions(IdentityMode::class, 'capell-access-gate::filament.identity_mode'))
                    ->required(),
                Select::make('approval_strategy')
                    ->label(__('capell-access-gate::filament.fields.approval_strategy'))
                    ->options(self::enumOptions(ApprovalStrategy::class, 'capell-access-gate::filament.approval_strategy'))
                    ->required(),
                TextInput::make('approval_limit')
                    ->label(__('capell-access-gate::filament.fields.approval_limit'))
                    ->numeric(),
                TextInput::make('grant_duration_days')
                    ->label(__('capell-access-gate::filament.fields.grant_duration_days'))
                    ->numeric(),
                Select::make('registration_policy')
                    ->label(__('capell-access-gate::filament.fields.registration_policy'))
                    ->options(self::enumOptions(RegistrationPolicy::class, 'capell-access-gate::filament.registration_policy'))
                    ->required(),
                Select::make('token_policy')
                    ->label(__('capell-access-gate::filament.fields.token_policy'))
                    ->options(self::enumOptions(TokenPolicy::class, 'capell-access-gate::filament.token_policy'))
                    ->required(),
                TagsInput::make('claim_url_hosts')
                    ->label(__('capell-access-gate::filament.fields.claim_url_hosts')),
                TagsInput::make('public_allowlist')
                    ->label(__('capell-access-gate::filament.fields.public_allowlist')),
                TextInput::make('gate_view')
                    ->label(__('capell-access-gate::filament.fields.gate_view')),
                TextInput::make('discount_label')
                    ->label(__('capell-access-gate::filament.fields.discount_label')),
                TextInput::make('discount_code')
                    ->label(__('capell-access-gate::filament.fields.discount_code')),
                DateTimePicker::make('discount_expires_at')
                    ->label(__('capell-access-gate::filament.fields.discount_expires_at')),
                KeyValue::make('metadata')
                    ->label(__('capell-access-gate::filament.fields.metadata'))
                    ->columnSpanFull(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('key')
                    ->label(__('capell-access-gate::filament.fields.key'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('capell-access-gate::filament.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.name')
                    ->label(__('capell-access-gate::filament.fields.site'))
                    ->placeholder(__('capell-access-gate::filament.fields.all_sites'))
                    ->sortable()
                    ->visible(fn (): bool => self::canScopeToSites()),
                TextColumn::make('status')
                    ->label(__('capell-access-gate::filament.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('identity_mode')
                    ->label(__('capell-access-gate::filament.fields.identity_mode'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('approval_strategy')
                    ->label(__('capell-access-gate::filament.fields.approval_strategy'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('registrations_count')
                    ->label(__('capell-access-gate::filament.fields.registrations'))
                    ->counts('registrations'),
                TextColumn::make('grants_count')
                    ->label(__('capell-access-gate::filament.fields.grants'))
                    ->counts('grants'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('capell-access-gate::filament.fields.status'))
                    ->options(self::enumOptions(AccessAreaStatus::class, 'capell-access-gate::filament.area_status')),
                SelectFilter::make('identity_mode')
                    ->label(__('capell-access-gate::filament.fields.identity_mode'))
                    ->options(self::enumOptions(IdentityMode::class, 'capell-access-gate::filament.identity_mode')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /** @return class-string<Area> */
    #[Override]
    public static function getModel(): string
    {
        return Area::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-access-gate::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-access-gate::filament.resources.access_areas');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(AccessGateServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccessAreas::route('/'),
            'create' => CreateAccessArea::route('/create'),
            'edit' => EditAccessArea::route('/{record}/edit'),
        ];
    }

    private static function canScopeToSites(): bool
    {
        return DatabaseSchema::hasTable('sites')
            && DatabaseSchema::hasColumn((new Area)->getTable(), 'site_id');
    }
}
