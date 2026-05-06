<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Models;

use Capell\LoginAudit\Database\Factories\LoginAuditFactory;
use Capell\LoginAudit\Observers\LoginAuditObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $authenticatable_type
 * @property int $authenticatable_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonImmutable|null $login_at
 * @property bool $login_successful
 * @property CarbonImmutable|null $logout_at
 * @property bool $cleared_by_user
 * @property array<array-key, mixed>|null $location
 * @property CarbonImmutable|null $last_seen_at
 * @property-read Model $authenticatable
 *
 * @method static Builder<static>|LoginAudit active()
 * @method static LoginAuditFactory factory($count = null, $state = [])
 * @method static Builder<static>|LoginAudit failed()
 * @method static Builder<static>|LoginAudit forUser($user)
 * @method static Builder<static>|LoginAudit fromDevice(string $deviceId)
 * @method static Builder<static>|LoginAudit fromIp(string $ip)
 * @method static Builder<static>|LoginAudit newModelQuery()
 * @method static Builder<static>|LoginAudit newQuery()
 * @method static Builder<static>|LoginAudit query()
 * @method static Builder<static>|LoginAudit recent(int $days = 7)
 * @method static Builder<static>|LoginAudit successful()
 * @method static Builder<static>|LoginAudit suspicious()
 * @method static Builder<static>|LoginAudit trusted()
 * @method static Builder<static>|LoginAudit whereAuthenticatableId($value)
 * @method static Builder<static>|LoginAudit whereAuthenticatableType($value)
 * @method static Builder<static>|LoginAudit whereClearedByUser($value)
 * @method static Builder<static>|LoginAudit whereId($value)
 * @method static Builder<static>|LoginAudit whereIpAddress($value)
 * @method static Builder<static>|LoginAudit whereLastSeenAt($value)
 * @method static Builder<static>|LoginAudit whereLocation($value)
 * @method static Builder<static>|LoginAudit whereLoginAt($value)
 * @method static Builder<static>|LoginAudit whereLoginSuccessful($value)
 * @method static Builder<static>|LoginAudit whereLogoutAt($value)
 * @method static Builder<static>|LoginAudit whereUserAgent($value)
 *
 * @mixin Model
 */
#[ObservedBy(LoginAuditObserver::class)]
class LoginAudit extends \Rappasoft\LaravelLoginAudit\Models\LoginAudit
{
    /** @use HasFactory<LoginAuditFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static string $factory = LoginAuditFactory::class;

    /**
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        return [
            ...$this->casts,
            'login_at' => 'immutable_datetime',
            'logout_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
        ];
    }
}
