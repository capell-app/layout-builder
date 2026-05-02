<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Models;

use Capell\AuthenticationLog\Database\Factories\AuthenticationLogFactory;
use Capell\AuthenticationLog\Observers\AuthenticationLogObserver;
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
 * @method static Builder<static>|AuthenticationLog active()
 * @method static AuthenticationLogFactory factory($count = null, $state = [])
 * @method static Builder<static>|AuthenticationLog failed()
 * @method static Builder<static>|AuthenticationLog forUser($user)
 * @method static Builder<static>|AuthenticationLog fromDevice(string $deviceId)
 * @method static Builder<static>|AuthenticationLog fromIp(string $ip)
 * @method static Builder<static>|AuthenticationLog newModelQuery()
 * @method static Builder<static>|AuthenticationLog newQuery()
 * @method static Builder<static>|AuthenticationLog query()
 * @method static Builder<static>|AuthenticationLog recent(int $days = 7)
 * @method static Builder<static>|AuthenticationLog successful()
 * @method static Builder<static>|AuthenticationLog suspicious()
 * @method static Builder<static>|AuthenticationLog trusted()
 * @method static Builder<static>|AuthenticationLog whereAuthenticatableId($value)
 * @method static Builder<static>|AuthenticationLog whereAuthenticatableType($value)
 * @method static Builder<static>|AuthenticationLog whereClearedByUser($value)
 * @method static Builder<static>|AuthenticationLog whereId($value)
 * @method static Builder<static>|AuthenticationLog whereIpAddress($value)
 * @method static Builder<static>|AuthenticationLog whereLastSeenAt($value)
 * @method static Builder<static>|AuthenticationLog whereLocation($value)
 * @method static Builder<static>|AuthenticationLog whereLoginAt($value)
 * @method static Builder<static>|AuthenticationLog whereLoginSuccessful($value)
 * @method static Builder<static>|AuthenticationLog whereLogoutAt($value)
 * @method static Builder<static>|AuthenticationLog whereUserAgent($value)
 *
 * @mixin Model
 */
#[ObservedBy(AuthenticationLogObserver::class)]
class AuthenticationLog extends \Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog
{
    /** @use HasFactory<AuthenticationLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static string $factory = AuthenticationLogFactory::class;

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
