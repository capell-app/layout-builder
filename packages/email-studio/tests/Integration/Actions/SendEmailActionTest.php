<?php

declare(strict_types=1);

use Capell\EmailStudio\Actions\SendEmailAction;
use Capell\EmailStudio\Data\EmailAddressData;
use Capell\EmailStudio\Data\EmailHeaderData;
use Capell\EmailStudio\Data\SendEmailData;
use Capell\EmailStudio\Enums\EmailMessageStatus;
use Capell\EmailStudio\Enums\EmailProviderType;
use Capell\EmailStudio\Enums\EmailRecipientStatus;
use Capell\EmailStudio\Exceptions\EmailStudioSendingException;
use Capell\EmailStudio\Jobs\SendEmailJob;
use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailProfile;
use Capell\EmailStudio\Models\EmailRecipient;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateVariant;
use Illuminate\Support\Facades\Queue;
use Spatie\LaravelData\DataCollection;

it('creates queued sends and rejects records scoped only to another site', function (): void {
    Queue::fake();

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

    EmailTemplateVariant::factory()->for($template, 'template')->create([
        'site_id' => 12,
        'site_scope_key' => 'site:12',
        'locale' => 'fr',
        'version' => 2,
        'subject' => 'Bonjour {{ name }}',
        'html_body' => '<p>Bonjour {{ name }}</p>',
        'text_body' => 'Bonjour {{ name }}',
    ]);

    $message = SendEmailAction::run(new SendEmailData(
        templateKey: 'forms.confirmation',
        to: new DataCollection(EmailAddressData::class, [
            new EmailAddressData('first@example.com', 'First Recipient'),
            new EmailAddressData('second@example.com', 'Second Recipient'),
        ]),
        cc: new DataCollection(EmailAddressData::class, []),
        bcc: new DataCollection(EmailAddressData::class, []),
        siteId: 12,
        siteScopeKey: 'site:12',
        emailProfileId: null,
        variables: ['name' => '<Ben>'],
        headers: new DataCollection(EmailHeaderData::class, []),
        triggeredByType: 'form_submission',
        triggeredById: 44,
        queue: true,
    ));

    expect($message)->toBeInstanceOf(EmailMessage::class)
        ->and($message->status)->toBe(EmailMessageStatus::Queued)
        ->and($message->site_id)->toBe(12)
        ->and($message->site_scope_key)->toBe('site:12')
        ->and($message->subject)->toBe('Hello &lt;Ben&gt;')
        ->and($message->rendered_html)->toContain('<p>Hello &lt;Ben&gt;</p>');

    expect(EmailRecipient::query()->where('email_message_id', $message->getKey())->count())->toBe(2)
        ->and(EmailRecipient::query()->where('email_message_id', $message->getKey())->pluck('status')->all())
        ->toBe([EmailRecipientStatus::Queued, EmailRecipientStatus::Queued]);

    Queue::assertPushed(SendEmailJob::class, fn (SendEmailJob $job): bool => $job->emailMessageId === $message->getKey());

    $localizedMessage = SendEmailAction::run(new SendEmailData(
        templateKey: 'forms.confirmation',
        to: new DataCollection(EmailAddressData::class, [new EmailAddressData('third@example.com')]),
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
        locale: 'fr',
    ));

    expect($localizedMessage->subject)->toBe('Bonjour Ben')
        ->and($localizedMessage->rendered_text)->toBe('Bonjour Ben');

    $messageCountBeforeEmptyRecipientSend = EmailMessage::query()->count();

    expect(fn (): EmailMessage => SendEmailAction::run(new SendEmailData(
        templateKey: 'forms.confirmation',
        to: new DataCollection(EmailAddressData::class, []),
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
    )))->toThrow(EmailStudioSendingException::class);

    expect(EmailMessage::query()->count())->toBe($messageCountBeforeEmptyRecipientSend);

    $messageCountBeforeRejectedSend = EmailMessage::query()->count();
    EmailProfile::factory()->create([
        'site_id' => 99,
        'site_scope_key' => 'site:99',
        'provider' => EmailProviderType::Fake,
        'is_default' => true,
    ]);

    $template = EmailTemplate::factory()->create([
        'site_id' => 99,
        'site_scope_key' => 'site:99',
        'key' => 'site.only',
    ]);

    EmailTemplateVariant::factory()->for($template, 'template')->create([
        'site_id' => 99,
        'site_scope_key' => 'site:99',
    ]);

    expect(fn (): EmailMessage => SendEmailAction::run(new SendEmailData(
        templateKey: 'site.only',
        to: new DataCollection(EmailAddressData::class, [new EmailAddressData('first@example.com')]),
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
    )))->toThrow(EmailStudioSendingException::class);

    expect(EmailMessage::query()->count())->toBe($messageCountBeforeRejectedSend);
});
