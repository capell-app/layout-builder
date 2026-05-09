<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Models;

use Capell\EmailStudio\Database\Factories\EmailProfileFactory;
use Capell\EmailStudio\Enums\EmailProviderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailProfile extends Model
{
    /** @use HasFactory<EmailProfileFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'site_scope_key',
        'name',
        'provider',
        'webhook_endpoint_token_hash',
        'from_email',
        'from_name',
        'reply_to_email',
        'reply_to_name',
        'is_default',
        'track_opens',
        'track_clicks',
        'provider_settings',
    ];

    protected static string $factory = EmailProfileFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-email-studio.tables.profiles');

        return is_string($tableName) ? $tableName : 'email_profiles';
    }

    public function variants(): HasMany
    {
        return $this->hasMany(EmailTemplateVariant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => EmailProviderType::class,
            'is_default' => 'boolean',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'provider_settings' => 'array',
        ];
    }
}
