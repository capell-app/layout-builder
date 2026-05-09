<?php

declare(strict_types=1);

use Capell\EmailStudio\Actions\DeliverEmailMessageAction;
use Capell\EmailStudio\Actions\SendEmailAction;
use Capell\EmailStudio\Actions\SuppressEmailAddressAction;
use Capell\EmailStudio\Contracts\EmailProviderAdapter;
use Capell\EmailStudio\Data\EmailAddressData;
use Capell\EmailStudio\Data\EmailHeaderData;
use Capell\EmailStudio\Data\InboundEmailReplyData;
use Capell\EmailStudio\Data\ProviderSendResultData;
use Capell\EmailStudio\Data\ProviderWebhookEventData;
use Capell\EmailStudio\Data\SendEmailData;
use Capell\EmailStudio\Enums\EmailMessageStatus;
use Capell\EmailStudio\Enums\EmailProviderType;
use Capell\EmailStudio\Enums\EmailRecipientStatus;
use Capell\EmailStudio\Enums\SuppressionReason;
use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailProfile;
use Capell\EmailStudio\Models\EmailRecipient;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateVariant;
use Capell\EmailStudio\Support\EmailProviderRegistry;
use Illuminate\Support\Facades\Queue;
use Spatie\LaravelData\DataCollection;

it('delivers queued recipients and rechecks suppressions before provider handoff', function (): void {
    Queue::fake();
    createEmailStudioSendFixtures();

    $message = SendEmailAction::run(new SendEmailData(
        templateKey: 'forms.confirmation',
        to: new DataCollection(EmailAddressData::class, [
            new EmailAddressData('first@example.com'),
            new EmailAddressData('second@example.com'),
        ]),
        cc: new DataCollection(EmailAddressData::class, []),
        bcc: new DataCollection(EmailAddressData::class, []),
        siteId: 12,
        siteScopeKey: 'site:12',
        emailProfileId: null,
        variables: ['name' => 'Ben'],
        headers: new DataCollection(EmailHeaderData::class, []),
        triggeredByType: null,
        triggeredById: null,
        queue: true,
    ));

    $deliveredMessage = DeliverEmailMessageAction::run($message);

    expect($deliveredMessage->status)->toBe(EmailMessageStatus::Sent)
        ->and($deliveredMessage->sent_at)->not->toBeNull();

    $recipients = EmailRecipient::query()->where('email_message_id', $message->getKey())->orderBy('email')->get();

    expect($recipients)->toHaveCount(2)
        ->and($recipients->pluck('status')->all())->toBe([
            EmailRecipientStatus::Sent,
            EmailRecipientStatus::Sent,
        ])
        ->and($recipients->pluck('provider_message_id')->all())->toBe([
            'fake-' . $message->getKey() . '-' . $recipients[0]->getKey(),
            'fake-' . $message->getKey() . '-' . $recipients[1]->getKey(),
        ]);

    $suppressionMessage = SendEmailAction::run(new SendEmailData(
        templateKey: 'forms.confirmation',
        to: new DataCollection(EmailAddressData::class, [
            new EmailAddressData('allowed@example.com'),
            new EmailAddressData('blocked@example.com'),
        ]),
        cc: new DataCollection(EmailAddressData::class, []),
        bcc: new DataCollection(EmailAddressData::class, []),
        siteId: 12,
        siteScopeKey: 'site:12',
        emailProfileId: null,
        variables: ['name' => 'Ben'],
        headers: new DataCollection(EmailHeaderData::class, []),
        triggeredByType: null,
        triggeredById: null,
        queue: true,
    ));

    SuppressEmailAddressAction::run(
        email: 'blocked@example.com',
        reason: SuppressionReason::Manual,
        siteId: 12,
        siteScopeKey: 'site:12',
        source: 'test',
    );

    $suppressedDeliveryMessage = DeliverEmailMessageAction::run($suppressionMessage);

    $allowedRecipient = EmailRecipient::query()->where('email', 'allowed@example.com')->sole();
    $blockedRecipient = EmailRecipient::query()->where('email', 'blocked@example.com')->sole();

    expect($suppressedDeliveryMessage->status)->toBe(EmailMessageStatus::PartiallyFailed)
        ->and($allowedRecipient->status)->toBe(EmailRecipientStatus::Sent)
        ->and($allowedRecipient->provider_message_id)->toBe('fake-' . $suppressionMessage->getKey() . '-' . $allowedRecipient->getKey())
        ->and($blockedRecipient->status)->toBe(EmailRecipientStatus::Suppressed)
        ->and($blockedRecipient->provider_message_id)->toBeNull()
        ->and($blockedRecipient->suppressed_at)->not->toBeNull();

    resolve(EmailProviderRegistry::class)->register(EmailProviderType::Fake, new class implements EmailProviderAdapter
    {
        public function send(EmailMessage $message): ProviderSendResultData
        {
            return new ProviderSendResultData(
                successful: false,
                failureReason: 'Provider rejected the message.',
            );
        }

        public function normalizeWebhookPayload(array $payload, array $headers = []): ProviderWebhookEventData
        {
            return new ProviderWebhookEventData(provider: 'fake', eventType: 'failed', payload: $payload);
        }

        public function normalizeInboundReply(array $payload, array $headers = []): InboundEmailReplyData
        {
            return new InboundEmailReplyData(provider: 'fake', providerMessageId: null, fromEmail: 'sender@example.com', payload: $payload);
        }
    });

    $providerFailureMessage = SendEmailAction::run(new SendEmailData(
        templateKey: 'forms.confirmation',
        to: new DataCollection(EmailAddressData::class, [new EmailAddressData('failure@example.com')]),
        cc: new DataCollection(EmailAddressData::class, []),
        bcc: new DataCollection(EmailAddressData::class, []),
        siteId: 12,
        siteScopeKey: 'site:12',
        emailProfileId: null,
        variables: ['name' => 'Ben'],
        headers: new DataCollection(EmailHeaderData::class, []),
        triggeredByType: null,
        triggeredById: null,
        queue: true,
    ));

    $failedDeliveryMessage = DeliverEmailMessageAction::run($providerFailureMessage);
    $failedRecipient = EmailRecipient::query()->where('email', 'failure@example.com')->sole();

    expect($failedDeliveryMessage->status)->toBe(EmailMessageStatus::Failed)
        ->and($failedDeliveryMessage->failed_at)->not->toBeNull()
        ->and($failedDeliveryMessage->failure_reason)->toBe('Provider rejected the message.')
        ->and($failedRecipient->status)->toBe(EmailRecipientStatus::Failed)
        ->and($failedRecipient->sent_at)->toBeNull()
        ->and($failedRecipient->provider_message_id)->toBeNull()
        ->and($failedRecipient->failure_reason)->toBe('Provider rejected the message.');

    resolve(EmailProviderRegistry::class)->register(EmailProviderType::Fake, new class implements EmailProviderAdapter
    {
        public function send(EmailMessage $message): ProviderSendResultData
        {
            throw new RuntimeException('Transport exploded.');
        }

        public function normalizeWebhookPayload(array $payload, array $headers = []): ProviderWebhookEventData
        {
            return new ProviderWebhookEventData(provider: 'fake', eventType: 'failed', payload: $payload);
        }

        public function normalizeInboundReply(array $payload, array $headers = []): InboundEmailReplyData
        {
            return new InboundEmailReplyData(provider: 'fake', providerMessageId: null, fromEmail: 'sender@example.com', payload: $payload);
        }
    });

    $exceptionFailureMessage = SendEmailAction::run(new SendEmailData(
        templateKey: 'forms.confirmation',
        to: new DataCollection(EmailAddressData::class, [new EmailAddressData('exception@example.com')]),
        cc: new DataCollection(EmailAddressData::class, []),
        bcc: new DataCollection(EmailAddressData::class, []),
        siteId: 12,
        siteScopeKey: 'site:12',
        emailProfileId: null,
        variables: ['name' => 'Ben'],
        headers: new DataCollection(EmailHeaderData::class, []),
        triggeredByType: null,
        triggeredById: null,
        queue: true,
    ));

    $exceptionDeliveryMessage = DeliverEmailMessageAction::run($exceptionFailureMessage);
    $exceptionRecipient = EmailRecipient::query()->where('email', 'exception@example.com')->sole();

    expect($exceptionDeliveryMessage->status)->toBe(EmailMessageStatus::Failed)
        ->and($exceptionDeliveryMessage->failure_reason)->toBe('Transport exploded.')
        ->and($exceptionRecipient->status)->toBe(EmailRecipientStatus::Failed)
        ->and($exceptionRecipient->failure_reason)->toBe('Transport exploded.');
});

function createEmailStudioSendFixtures(): void
{
    EmailProfile::factory()->create([
        'site_id' => 12,
        'site_scope_key' => 'site:12',
        'provider' => EmailProviderType::Fake,
        'is_default' => true,
    ]);

    $template = EmailTemplate::factory()->create([
        'site_id' => 12,
        'site_scope_key' => 'site:12',
        'key' => 'forms.confirmation',
        'variables' => ['name'],
    ]);

    EmailTemplateVariant::factory()->for($template, 'template')->create([
        'site_id' => 12,
        'site_scope_key' => 'site:12',
        'locale' => 'en',
        'subject' => 'Hello {{ name }}',
        'html_body' => '<p>Hello {{ name }}</p>',
        'text_body' => 'Hello {{ name }}',
    ]);
}
