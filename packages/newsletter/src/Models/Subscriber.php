<?php

declare(strict_types=1);

namespace Capell\Newsletter\Models;

use Capell\Core\Models\Site;
use Capell\Newsletter\Database\Factories\SubscriberFactory;
use Capell\Newsletter\Enums\SubscriberStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Tags\HasTags;

/**
 * @property SubscriberStatus $status
 * @property CarbonInterface|null $subscribed_at
 * @property CarbonInterface|null $unsubscribed_at
 * @property CarbonInterface|null $updated_at
 */
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    use HasTags;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'email_hash',
        'email',
        'first_name',
        'last_name',
        'profile',
        'status',
        'source_form_id',
        'source_form_handle',
        'pending_at',
        'subscribed_at',
        'confirmed_at',
        'unsubscribed_at',
        'suppressed_at',
        'bounced_at',
        'complained_at',
    ];

    protected $table = 'newsletter_subscribers';

    protected static string $factory = SubscriberFactory::class;

    public static function emailHash(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sourceForm(): BelongsTo
    {
        return $this->belongsTo($this->formModelClass(), 'source_form_id');
    }

    public function consentEvents(): HasMany
    {
        return $this->hasMany(ConsentEvent::class);
    }

    public function publicTokens(): HasMany
    {
        return $this->hasMany(PublicToken::class);
    }

    public function providerSubscribers(): HasMany
    {
        return $this->hasMany(ProviderSubscriber::class);
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(
            Segment::class,
            'newsletter_segment_subscriber',
            'newsletter_subscriber_id',
            'newsletter_segment_id',
        )->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saving(function (Subscriber $subscriber): void {
            if (is_string($subscriber->email) && $subscriber->email !== '') {
                $subscriber->email_hash = self::emailHash($subscriber->email);
            }
        });
    }

    protected function scopeForEmail(Builder $query, int $siteId, string $email): Builder
    {
        return $query
            ->where('site_id', $siteId)
            ->where('email_hash', self::emailHash($email));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'first_name' => 'encrypted',
            'last_name' => 'encrypted',
            'profile' => 'encrypted:array',
            'status' => SubscriberStatus::class,
            'pending_at' => 'datetime',
            'subscribed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'suppressed_at' => 'datetime',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
        ];
    }

    private function formModelClass(): string
    {
        return implode('\\', ['Capell', 'FormBuilder', 'Models', 'Form']);
    }
}
