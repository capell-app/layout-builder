<?php

declare(strict_types=1);

use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailProfile;
use Capell\EmailStudio\Models\EmailRecipient;
use Capell\EmailStudio\Models\EmailReply;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateVariant;

it('links templates, variants, messages, recipients, and replies', function (): void {
    $profile = EmailProfile::factory()->create();
    $template = EmailTemplate::factory()->create();
    $variant = EmailTemplateVariant::factory()->for($template, 'template')->create();
    $message = EmailMessage::factory()
        ->for($profile, 'profile')
        ->for($template, 'template')
        ->for($variant, 'templateVariant')
        ->create();
    $recipient = EmailRecipient::factory()->for($message, 'message')->create();
    $reply = EmailReply::factory()
        ->for($message, 'message')
        ->for($recipient, 'recipient')
        ->create();

    expect($message->template->is($template))->toBeTrue()
        ->and($message->templateVariant->is($variant))->toBeTrue()
        ->and($message->recipients)->toHaveCount(1)
        ->and($recipient->replies)->toHaveCount(1)
        ->and($reply->message->is($message))->toBeTrue();
});
