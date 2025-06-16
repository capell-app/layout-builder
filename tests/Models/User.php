<?php

declare(strict_types=1);

namespace Capell\Tests\Models;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Capell\Core\Database\Factories\UserFactory;
use Capell\Core\Models\Media;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Traits\HasRoles;

class User extends Model implements Auditable, AuthenticatableContract, AuthorizableContract, FilamentUser
{
    use Authenticatable;
    use AuthenticationLoggable;
    use Authorizable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasPanelShield;
    use HasRoles;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'profile_image_id',
        'avatar',
    ];

    /**
     * The guard name for the model.
     *
     * @var array
     */
    protected string $guard_name = 'web';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected static string $factory = UserFactory::class;

    public function profileImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'profile_image_id');
    }
}
