<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Extenders;

use Capell\Admin\Actions\Users\ShouldLoadUserResourceBridgeAction;
use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Support\Schemas\AbstractUserSchemaExtender;
use Capell\LoginAudit\Filament\Resources\Users\RelationManagers\LoginAuditsRelationManager;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\LoginAudit\Settings\LoginAuditSettings;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Override;

final class LoginAuditUserSchemaExtender extends AbstractUserSchemaExtender
{
    #[Override]
    public function supports(UserSchemaContextData $context): bool
    {
        if (! ShouldLoadUserResourceBridgeAction::run('enable_security_access_user_bridge', true)) {
            return false;
        }

        return ShouldLoadUserResourceBridgeAction::run(
            'enable_login_audit_user_bridge',
            resolve(LoginAuditSettings::class)->enable_user_resource_bridge,
        );
    }

    #[Override]
    public function extendSidebarComponents(Schema $schema, UserSchemaContextData $context): array
    {
        if (! $context->record instanceof Model) {
            return [];
        }

        $record = $context->record;

        return [
            Section::make(__('capell-login-audit::settings.security_access'))
                ->compact()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            $this->summaryText('recent_logins', fn (): int => $this->auditsFor($record)
                                ->where('login_successful', true)
                                ->where('login_at', '>=', now()->subDays(7))
                                ->count()),
                            $this->summaryText('failed_attempts', fn (): int => $this->auditsFor($record)
                                ->where('login_successful', false)
                                ->where('login_at', '>=', now()->subDays(7))
                                ->count()),
                            $this->summaryText('recent_devices', fn (): int => $this->auditsFor($record)
                                ->whereNotNull('device_id')
                                ->where('login_at', '>=', now()->subDays(30))
                                ->distinct()
                                ->count('device_id')),
                            $this->summaryText('active_sessions', fn (): int => $this->auditsFor($record)
                                ->where('login_successful', true)
                                ->whereNull('logout_at')
                                ->count()),
                        ]),
                ]),
        ];
    }

    #[Override]
    public function extendRelationManagers(Model $record, array $relationManagers, UserSchemaContextData $context): array
    {
        if (! method_exists($record, 'authentications')) {
            return $relationManagers;
        }

        if (in_array(LoginAuditsRelationManager::class, $relationManagers, true)) {
            return $relationManagers;
        }

        return [
            ...$relationManagers,
            LoginAuditsRelationManager::class,
        ];
    }

    private function summaryText(string $translationKey, callable $count): Text
    {
        return Text::make(fn (): string => sprintf(
            '%s: %s',
            __('capell-login-audit::settings.' . $translationKey),
            Number::format($count()),
        ));
    }

    /**
     * @return Builder<LoginAudit>
     */
    private function auditsFor(Model $record): Builder
    {
        return LoginAudit::query()
            ->where('authenticatable_type', $record->getMorphClass())
            ->where('authenticatable_id', $record->getKey());
    }
}
